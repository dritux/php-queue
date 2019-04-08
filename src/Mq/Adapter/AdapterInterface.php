<?php
namespace Dr\Mq\Adapter;

use Dr\Mq\EnvelopeInterface;

interface AdapterInterface
{
    /**
     * Connects to message queue underlying transport
     *
     * @return AdapterInterface
     */
    public function connect();
    /**
     * Stops listening for incoming messages
     *
     * @return AdapterInterface
     */
    public function stop();
    /**
     * Closes the message
     *
     * @return AdapterInterface
     */
    public function close();
    /**
     * Publishes a message
     *
     * @param EnvelopeInterface $message
     * @return EnvelopeInterface
     */
    public function publish(EnvelopeInterface $message);
    /**
     * @param string $queue
     * @return null
     */
    public function subscribe($queue, \Closure $onMessage, array $params = array());
    /**
     * Acknowledges a message
     *
     * @param EnvelopeInterface $message
     * @return AdapterInterface
     */
    public function ack(EnvelopeInterface $message);
    /**
     * Reject a message
     *
     * @param EnvelopeInterface $message
     * @param array $params Params
     * @return \Dr\Mq\Adapter\AdapterInterface
     */
    public function nack(EnvelopeInterface $message, array $params = array());
}
