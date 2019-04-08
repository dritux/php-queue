<?php
namespace Dr\Mq;

use Dr\Mq\Adapter\InMemory\InMemory;
use Dr\Mq\Adapter\InMemory\Message;

class MessageQueueTest extends \PHPUnit_Framework_TestCase
{
    public function testCannotPublishWhenNoConnection()
    {
        $this->setExpectedException(
            'LogicException',
            'Not connected! Open the connection first',
            763
        );

        $mq = new MessageQueue(new InMemory());
        $mq->publish(new Message('test_queue'));
    }

    public function testCannotSubscribenWhenNoConnection()
    {
        $this->setExpectedException(
            'LogicException',
            'Not connected! Open the connection first',
            763
        );

        $mq = new MessageQueue(new InMemory());
        $mq->subscribe('test_queue', function () {
            //
        });
    }

    public function testCannotAckWhenNoConnection()
    {
        $this->setExpectedException(
            'LogicException',
            'Not connected! Open the connection first',
            763
        );

        $mq = new MessageQueue(new InMemory());
        $mq->ack(new Message('test_queue'));
    }

    public function testCannotNackWhenNoConnection()
    {
        $this->setExpectedException(
            'LogicException',
            'Not connected! Open the connection first',
            763
        );

        $mq = new MessageQueue(new InMemory());
        $mq->nack(new Message('test_queue'));
    }

    public function testPublishMessages()
    {
        $adapter = new InMemory();
        $this->assertEquals(0, count($adapter));

        $mq = new MessageQueue($adapter);
        $mq->connect();

        foreach ($this->messageProvider() as $message) {
            $message = $mq->publish($message);

            $this->assertNotNull($message->getId());
            $this->assertEquals('test_queue', $message->getQueue());
        }

        $this->assertEquals(5, count($adapter));
    }

    public function testSubscribenForMessagesInOrder()
    {
        $messageProvision = $this->messageProvider();
        $adapter = new InMemory($messageProvision);

        $this->assertEquals(5, count($adapter));
    }

    public function testNackMessages()
    {
        $adapter = new InMemory($this->messageProvider());

        $this->assertEquals(5, count($adapter));

    }

    /**
     * @param int $num
     * @return \ArrayObject
     */
    private function messageProvider($num = 5)
    {
        $messages = new \ArrayObject();

        for ($i = 0; $i < $num; $i++) {
            $message = new Message('test_queue');
            $message
                ->setBody('test_body_' . $i)
                ->setAttribute('test_attr_name_' . $i, 'test_attr_value_' . $i)
                ->setAttribute('index', $i);
            $messages[] = $message;
        }

        return $messages;
    }
}
