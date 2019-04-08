<?php
namespace Dr\Mq\Adapter\PubSub;

use Google\Cloud\PubSub\Subscription;

use Dr\Mq\EnvelopeInterface;

class Message implements EnvelopeInterface
{
    /** @var string */
    private $queue;

    /** @var string */
    private $receiptHandle;

    /** @var array */
    private $attributeBag = array();

    /** @var string */
    private $body = '';

    /**
     * @param string $queue
     */
    public function __construct($queue)
    {
        $this->queue = $queue;
    }
    /**
     * Assigned as the message_id message attribute
     *
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->setAttribute('message_id', $id);
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getAttribute('message_id');
    }
    /**
     * {@inheritdoc}
     */
    public function setReceiptHandle($receiptHandle)
    {
        $this->receiptHandle = $receiptHandle;
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function getReceiptHandle()
    {
        return $this->receiptHandle;
    }
    /**
     * {@inheritdoc}
     */
    public function getQueue()
    {
        return $this->queue;
    }
    /**
     * {@inheritdoc}
     */
    public function setAttribute($name, $value)
    {
        $this->attributeBag[$name] = $value;
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null)
    {
        if ($this->hasAttribute($name)) {
            return $this->attributeBag[$name];
        }
        return $default;
    }
    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributeBag;
    }
    /**
     * {@inheritdoc}
     */
    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributeBag);
    }
    /**
     * {@inheritdoc}
     */
    public function removeAttribute($name)
    {
        if ($this->hasAttribute($name)) {
            unset($this->attributeBag[$name]);
        }
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function clearAttributes()
    {
        $this->attributeBag = array();
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }
    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array(
            'queue' => $this->getQueue(),
            'body' => $this->getBody(),
            'attributes' => $this->getAttributes()
        );
    }
    /**
     * {@inheritdoc}
     */
    public function toVendor()
    {
        $message = [];
        return $message;
    }
    /**
     * {@inheritdoc}
     */
    public static function fromVendor($subscription)
    {
        if (!$subscription instanceof Subscription) {
            throw new \InvalidArgumentException(sprintf(
                'Subscription class instance excpected. Given type is: %s',
                gettype($subscription)
            ));
        }
        $self = new static($subscription);
        $messages = $subscription->pull();
        if (!is_array($messages)) {
            throw new \InvalidArgumentException(sprintf(
                'Array type excpected from PubSub message. Given type is: %s',
                gettype($data)
            ));
        }
        $self->setBody($messages);
        return $self;
    }
}
