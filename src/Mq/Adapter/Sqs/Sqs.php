<?php
namespace Dr\Mq\Adapter\Sqs;

use Dr\Mq\Adapter\AdapterInterface;
use Dr\Mq\EnvelopeInterface;
use Dr\Mq\Adapter\AdapterException;
use Aws\Common\Aws;
use Aws\Sqs\SqsClient;
use Aws\Sqs\Enum\MessageAttribute;
use Aws\Sqs\Exception\SqsException;

class Sqs implements AdapterInterface
{
    /** Nack option key for visibility timeout value */
    const NACK_OPT_TIMEOUT = 'VisibilityTimeout';
    /** Used for metric namespacing */
    const METRIC_PREFIX = 'mq.sqs.';
    /** SQS receive message max long polling value */
    const MAX_POLLING_TIME = 20;
    /** @var Aws */
    private $aws;
    /** @var SqsClient */
    private $client;
    /** @var bool */
    private $consuming;

    /**
     * @see http://docs.aws.amazon.com/aws-sdk-php/v2/guide/credentials.html
     *
     * @param string $region
     * @param string $key
     * @param string $secret
     */
    public function __construct($region, $key, $secret)
    {
        $this->aws = Aws::factory(array(
            'region' => $region,
            'credentials' => array(
                'key' => $key,
                'secret' => $secret
            )
        ));
    }
    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        $this->client = $this->aws->get('Sqs');
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function stop()
    {
        $this->consuming = false;
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function close()
    {
        $this->client = null;
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function subscribe($queue, \Closure $onMessage, array $params = array())
    {
        $this->consuming = true;
        $polling = $initialPolling = $this->resolveInitialPolling($params);
        $params = array_merge(
            array(
                'QueueUrl' => $queue,
                'MessageAttributeNames' => array(MessageAttribute::ALL),
                'AttributeNames' => array(MessageAttribute::ALL)
            ),
            $params,
            array('MaxNumberOfMessages' => 1)
        );
        while ($this->consuming) {
            $params['WaitTimeSeconds'] = $polling;
            $message = $this->fetchMessage($params);
            if ($message) {
                $onMessage($message);
                if ($polling > $initialPolling) {
                    $polling = $initialPolling;
                }
            }
            if ($polling < self::MAX_POLLING_TIME) {
                $polling++;
            }
        }
    }
    /**
     * {@inheritDoc}
     */
    public function publish(EnvelopeInterface $message)
    {
        try {
            $result = $this->client->sendMessage($message->toVendor());
            $message
                ->setId($result->getPath('MessageId'))
                ->setAttribute('MD5OfMessageBody', $result->getPath('MD5OfMessageBody'));
            $attrMd5 = $result->getPath('MD5OfMessageAttributes');
            if ($attrMd5) {
                $message->setAttribute('MD5OfMessageAttributes', $attrMd5);
            }
            return $message;
        } catch (SqsException $e) {
            throw new AdapterException($e->getMessage(), $e->getCode(), $e);
        }
    }
    /**
     * {@inheritDoc}
     */
    public function ack(EnvelopeInterface $message)
    {
        try {
            $this->client->deleteMessage(array(
                'QueueUrl' => $message->getQueue(),
                'ReceiptHandle' => $message->getReceiptHandle()
            ));
            return $this;
        } catch (SqsException $e) {
            throw new AdapterException($e->getMessage(), $e->getCode(), $e);
        }
    }
    /**
     * {@inheritDoc}
     */
    public function nack(EnvelopeInterface $message, array $params = array())
    {
        $params = array_merge(
            array(
                'QueueUrl' => $message->getQueue(),
                'ReceiptHandle' => $message->getReceiptHandle(),
                self::NACK_OPT_TIMEOUT => 0
            ),
            $params
        );
        try {
            $this->client->changeMessageVisibility($params);
            return $this;
        } catch (SqsException $e) {
            throw new AdapterException($e->getMessage(), $e->getCode(), $e);
        }
    }
    /**
     * Returns the default polling value for message fetching
     *
     * @param array $params
     * @return int
     */
    private function resolveInitialPolling(array $params)
    {
        if (array_key_exists('WaitTimeSeconds', $params)) {
            $pollingValue = $params['WaitTimeSeconds'];
            if ($pollingValue > self::MAX_POLLING_TIME) {
                return self::MAX_POLLING_TIME;
            }
            if ($pollingValue < 0) {
                return 0;
            }
            return $pollingValue;
        }
        return 0;
    }
    /**
     * Fetches a SQS message
     *
     * @param array $params
     * @return EnvelopeInterface|null
     * @throws AdapterException
     */
    private function fetchMessage(array $params)
    {
        try {
            $results = $this->client->receiveMessage($params);
            $message = $results->getPath('Messages/0');
            if (!$message) {
                return null;
            }
            $message['QueueUrl'] = $params['QueueUrl'];
            return Message::fromVendor($message);
        } catch (SqsException $e) {
            throw new AdapterException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
