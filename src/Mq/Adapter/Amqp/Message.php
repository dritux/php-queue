<?php
namespace Dr\Mq\Adapter\Amqp;

use Dr\Mq\EnvelopeInterface;

use PhpAmqpLib\Message\AMQPMessage;

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

    public function setId($id)
    {
        $this->setAttribute('message_id', $id);

        return $this;
    }

    public function getId()
    {
        return $this->getAttribute('message_id');
    }

    public function setReceiptHandle($receiptHandle)
    {
        $this->receiptHandle = $receiptHandle;

        return $this;
    }

    public function getReceiptHandle()
    {
        return $this->receiptHandle;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function setAttribute($name, $value)
    {
        $this->attributeBag[$name] = $value;

        return $this;
    }

    public function getAttribute($name, $default = null)
    {
        if ($this->hasAttribute($name)) {
            return $this->attributeBag[$name];
        }

        return $default;
    }

    public function getAttributes()
    {
        return $this->attributeBag;
    }

    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributeBag);
    }

    public function removeAttribute($name)
    {
        if ($this->hasAttribute($name)) {
            unset($this->attributeBag[$name]);
        }

        return $this;
    }

    public function clearAttributes()
    {
        $this->attributeBag = array();

        return $this;
    }

    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function toArray()
    {
        return array(
            'queue' => $this->getQueue(),
            'body' => $this->getBody(),
            'delivery_tag' => $this->getReceiptHandle(),
            'attributes' => $this->getAttributes()
        );
    }

    public function toVendor()
    {
        $message = new AMQPMessage($this->getBody());
        foreach ($this->getAttributes() as $name => $value) {
            $message->set($name, $value);
        }

        return $message;
    }

    public static function fromVendor($data)
    {
        if (!$data instanceof AMQPMessage) {
            throw new \InvalidArgumentException(sprintf(
                'AMQPMessage class instance excpected. Given type is: %s',
                gettype($data)
            ));
        }

        /** @var Message $self */
        $self = new static($data->get('routing_key'));
        $self
            ->setBody($data->body)
            ->setReceiptHandle($data->get('delivery_tag'));
        return $self;
    }
}
