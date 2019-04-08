<?php
namespace Dr\Mq;

use Dr\Mq\Adapter\AdapterException;
use Dr\Mq\Adapter\AdapterInterface;
use Dr\Mq\MetricDirectory;
use Beberlei\Metrics\Collector\NullCollector;
use Beberlei\Metrics\Collector\Collector;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MessageQueue implements LoggerAwareInterface
{
    /** @var AdapterInterface */
    private $adapter;
    /** @var LoggerInterface */
    private $logger;
    /** @var Collector */
    private $collector;
    /** @var string */
    private $collectorPrefix = '';
    /** @var float */
    private $processingTimer;
    /** @var bool */
    private $connected = false;
    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this
            ->setLogger(new NullLogger())
            ->setMetricCollector(new NullCollector(), $adapter::METRIC_PREFIX);
    }

    /**
     * @return MessageQueue
     * @throws AdapterException
     */
    public function connect()
    {
        try {
            $this->logger->info('Opening the connection...');
            $start = microtime(true);
            $this->adapter->connect();
            $this->connected = true;
            $duration = (microtime(true) - $start) * 1000;
            $this->collector->increment($this->collectorPrefix . MetricDirectory\CONNECTION_OPEN_SUCCEED);
            $this->collector->timing($this->collectorPrefix . MetricDirectory\CONNECTION_OPEN_TIME, $duration);
            $this->collector->flush();

            $this->logger->info('Opened the connection');
            return $this;
        } catch (AdapterException $e) {
            $this->logger->critical(sprintf('%s (%s)', $e->getMessage(), $e->getCode()));
            $this->logger->debug($e->getLine(), $e->getTrace());

            $this->collector->increment($this->collectorPrefix . MetricDirectory\CONNECTION_OPEN_FAILED);
            $this->collector->flush();
            throw $e;
        }
    }
    /**
     * @return MessageQueue
     * @throws AdapterException
     */
    public function stop()
    {
        try {
            $this->logger->info('Stopping to subscribe to incoming messages...');
            $start = microtime(true);
            $this->adapter->stop();
            $duration = (microtime(true) - $start) * 1000;
            $this->collector->increment($this->collectorPrefix . MetricDirectory\CONNECTION_STOP_SUCCEED);
            $this->collector->timing($this->collectorPrefix . MetricDirectory\CONNECTION_STOP_TIME, $duration);
            $this->collector->flush();
            $this->logger->info('Stopped to subscribe to incoming messages');
            return $this;
        } catch (AdapterException $e) {
            $this->logger->error(sprintf('%s (%s)', $e->getMessage(), $e->getCode()));
            $this->logger->debug($e->getLine(), $e->getTrace());

            $this->collector->increment($this->collectorPrefix . MetricDirectory\CONNECTION_STOP_FAILED);
            $this->collector->flush();
            throw $e;
        }
    }
    /**
     * @return MessageQueue
     * @throws AdapterException
     */
    public function close()
    {
        try {
            $this->stop();
            $this->logger->info('Closing the connection...');
            $start = microtime(true);
            $this->adapter->close();
            $this->connected = false;
            $duration = (microtime(true) - $start) * 1000;
            $this->collector->increment($this->collectorPrefix . MetricDirectory\CONNECTION_CLOSE_SUCCEED);
            $this->collector->timing($this->collectorPrefix . MetricDirectory\CONNECTION_CLOSE_TIME, $duration);
            $this->collector->flush();
            $this->logger->info('Closed the connection');
            return $this;
        } catch (AdapterException $e) {
            $this->logger->error(sprintf('%s (%s)', $e->getMessage(), $e->getCode()));
            $this->logger->debug($e->getLine(), $e->getTrace());

            $this->collector->increment($this->collectorPrefix . MetricDirectory\CONNECTION_CLOSE_FAILED);
            $this->collector->flush();
            throw $e;
        }
    }
    /**
     * @param EnvelopeInterface $message
     * @return EnvelopeInterface
     * @throws AdapterException
     */
    public function publish(EnvelopeInterface $message)
    {
        $this->ensureIsConnected();
        try {
            $this->logger->debug('Publishing message', array('message' => $message->toArray()));
            $start = microtime(true);

            $publish = $this->adapter->publish($message);

            $duration = (microtime(true) - $start) * 1000;

            $this->logger->info(sprintf('Published message %s in %s ms', $message->getId(), $duration));
            $this->logger->debug('Published message', array('message' => $message->toArray()));

            $this->collector->increment($this->collectorPrefix . MetricDirectory\MESSAGE_PUBLISH_SUCCEED);
            $this->collector->timing($this->collectorPrefix . MetricDirectory\MESSAGE_PUBLISH_TIME, $duration);
            $this->collector->flush();

            return $publish;
        } catch (AdapterException $e) {
            $this->logger->error(sprintf('%s (%s)', $e->getMessage(), $e->getCode()));
            $this->logger->debug($e->getLine(), $e->getTrace());
            
            $this->collector->increment($this->collectorPrefix . MetricDirectory\MESSAGE_PUBLISH_FAILED);
            $this->collector->flush();
            throw $e;
        }
    }
    /**
     * @param string $queue
     * @return null
     * @throws AdapterException
     */
    public function subscribe($queue, callable $onMessage, array $params = array())
    {
        $this->ensureIsConnected();
        try {
            $start = microtime(true);

            return $this->adapter->subscribe(
                $queue,
                function (EnvelopeInterface $message) use (&$start, $onMessage) {
                    $duration = (microtime(true) - $start) * 1000;
                    $this->logger->info(sprintf('Fetched message %s in %s ms', $message->getId(), $duration));
                    $this->logger->debug('Fetched message', array('message' => $message->toArray()));

                    $this->collector->increment($this->collectorPrefix . MetricDirectory\MESSAGE_FETCH_SUCCEED);
                    $this->collector->timing($this->collectorPrefix . MetricDirectory\MESSAGE_FETCH_TIME, $duration);
                    // Start processing timer
                    $this->processingTimer = microtime(true);
                    $onMessage($message, $this);
                    // Resets timer
                    $start = microtime(true);
                },
                $params
            );
        } catch (AdapterException $e) {
            $this->logger->error(sprintf('%s (%s)', $e->getMessage(), $e->getCode()));
            $this->logger->debug($e->getLine(), $e->getTrace());
            $this->collector->increment($this->collectorPrefix . MetricDirectory\MESSAGE_LISTEN_FAILED);
            $this->collector->flush();
            throw $e;
        }
    }
    /**
     * @param EnvelopeInterface $message
     * @return MessageQueue
     * @throws AdapterException
     */
    public function ack(EnvelopeInterface $message)
    {
        $this->ensureIsConnected();
        try {
            $this->collectProcessingTime();

            $this->logger->debug('Acking message', array('message' => $message->toArray()));

            $start = microtime(true);
            $this->adapter->ack($message);
            $duration = (microtime(true) - $start) * 1000;

            $this->logger->info(sprintf('Acked message %s in %s ms', $message->getId(), $duration));

            $this->collector->increment($this->collectorPrefix . MetricDirectory\MESSAGE_ACK_SUCCEED);
            $this->collector->timing($this->collectorPrefix . MetricDirectory\MESSAGE_ACK_TIME, $duration);
            $this->collector->flush();

            return $this;
        } catch (AdapterException $e) {
            throw $e;
        }
    }
    /**
     * @param EnvelopeInterface $message
     * @param array $params
     * @return MessageQueue
     * @throws AdapterException
     */
    public function nack(EnvelopeInterface $message, array $params = array())
    {
        $this->ensureIsConnected();
        try {
            $this->collectProcessingTime();

            $this->logger->debug('Nacking message', array('message' => $message->toArray()));

            $start = microtime(true);
            $this->adapter->nack($message, $params);
            $duration = (microtime(true) - $start) * 1000;

            $this->logger->info(sprintf('Nacked message %s in %s ms', $message->getId(), $duration));

            $this->collector->increment($this->collectorPrefix . MetricDirectory\MESSAGE_NACK_SUCCEED);
            $this->collector->timing($this->collectorPrefix . MetricDirectory\MESSAGE_NACK_TIME, $duration);
            $this->collector->flush();
            return $this;
        } catch (AdapterException $e) {
            $this->logger->error(sprintf(
                '%s (%s)',
                $e->getMessage(),
                $e->getCode()
            ));
            $this->logger->debug($e->getLine(), $e->getTrace());
            $this->collector->increment($this->collectorPrefix . MetricDirectory\MESSAGE_NACK_FAILED);
            $this->collector->flush();
            throw $e;
        }
    }
    /**
     * This method should be called in every action that requires the connection
     * to be opened.
     */
    private function ensureIsConnected()
    {
        if (!$this->connected) {
            throw new \LogicException('Not connected! Open the connection first', 763);
        }
    }
    /**
     * Collects the message processing time
     */
    private function collectProcessingTime()
    {
        if (!$this->processingTimer) {
            return;
        }
        $duration = microtime(true) - $this->processingTimer;
        $this->collector->timing($this->collectorPrefix . MetricDirectory\MESSAGE_PROCESS_TIME, ($duration * 1000));
    }

    /**
     * {@inheritDoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @param Collector $collector
     * @param string $prefix Collector prefix to use for MQ metrics
     * @return MessageQueue
     */
    public function setMetricCollector(Collector $collector, $prefix)
    {
        $this->collector = $collector;
        $this->collectorPrefix = $prefix;
        return $this;
    }

    /**
     * @return Collector
     */
    public function getMetricCollector()
    {
        return $this->collector;
    }
}
