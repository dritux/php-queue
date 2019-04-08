<?php
namespace Dr\Mq\Adapter\PubSub;

use Dr\Mq\Adapter\AdapterInterface;
use Dr\Mq\EnvelopeInterface;
use Dr\Mq\Adapter\AdapterException;

use Google\Cloud\Core\Exception;

class PubSub implements AdapterInterface
{
    /** Nack option key for visibility timeout value */
    const NACK_OPT_TIMEOUT = 'VisibilityTimeout';

    /** Used for metric namespacing */
    const METRIC_PREFIX = 'mq.pubsub.';

    /** @var Connection */
    private $connection;

    /** @var string */
    private $subscribe;

    /** @var bool */
    private $consuming;

    /** @var string */
    private $topic;

    /** @var string */
    private $subscription;

    /**
     * @param Connection $connection
     * @param string $topic
     */
    public function __construct(Connection $connection, $topic)
    {
        $this->connection = $connection;
        $this->topic = $this->topic($topic);
    }
    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        try {
            $this->connection->connect();
            return $this;
        } catch (\Exception $e) {
            throw new AdapterException($e->getMessage(), $e->getCode(), $e);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function publish(EnvelopeInterface $message)
    {
        $pubsub = $this->connection->connect();
        $topic = $pubsub->topic($this->topic->name());

        $publish = $topic->publish([
            'data' => json_encode($message->getBody()),
            'attributes' => [
                'retries' => '1',
                'timeout' => '10'
            ]
        ]);

        return $publish;
    }
    /**
     * {@inheritdoc}
     */
    public function subscribe($subscriptionName, \Closure $onMessage, array $params = array())
    {
        $pubsub = $this->connection->connect();
        $this->subscription = $this->subscription($subscriptionName);
        $this->subscribe = $pubsub->subscription($subscriptionName);
        $onMessage(Message::fromVendor($this->subscribe));
    }
    /**
     * {@inheritdoc}
     */
    public function ack(EnvelopeInterface $message)
    {
        $this->subscribe->acknowledge($message->getReceiptHandle());
    }
    /**
     * {@inheritdoc}
     */
    public function nack(EnvelopeInterface $message, array $params = array())
    {
        // retry
    }
    /**
     * @return Object
     */
    public function topic($name)
    {
        try {
            $pubsub = $this->connection->connect();
            $topic = $pubsub->topic($name);
            $topic->exists() === true ? true : $topic->create();
            return $topic;
        } catch (GoogleException $e) {
            print $e->getMessage();
            return $e->getMessage();
        }
    }
    /**
     * @return Object
     */
    public function subscription($subscriptionName)
    {
        try {
            $pubsub = $this->connection->connect();
            $subscription = $this->topic->subscription($subscriptionName);
            $subscription->exists() === true ? true : $subscription->create();
            return $subscription;
        } catch (GoogleException $e) {
            print $e->getMessage();
            return $e->getMessage();
        }
    }
    /**
     * {@inheritdoc}
     */
    public function stop()
    {
    }
    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }

}
