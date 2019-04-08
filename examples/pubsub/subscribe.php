<?php
use Dr\Mq\MessageQueue;
use Dr\Mq\Adapter\PubSub\Connection;
use Dr\Mq\Adapter\PubSub\PubSub;
use Dr\Mq\Adapter\PubSub\Message;
use Dr\Mq\EnvelopeInterface;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/../../vendor/autoload.php';


$logger = new Logger('Subscriber');
$logger->pushHandler(new StreamHandler('/var/log/php-queue/subscribe.log'));


$adapter = new PubSub(
    new Connection("poc-pubsub-b1981"),
    "php-backend"
);

$mq = new MessageQueue($adapter);
$mq->setLogger($logger)->connect();

$subscription = $mq->subscribe(
    "sale-subscription",
    function (EnvelopeInterface $message, MessageQueue $mq) {
        if (empty($message->getBody())) {
            return false;
        }
        $messages = $message->getBody();
        foreach ($messages as $m) {
            try {
                $data = json_decode($m->data());

                $message->setReceiptHandle($m);
                $mq->ack($message);
            } catch (\Exception $e) {
                $mq->nack($message, array(PubSub::NACK_OPT_TIMEOUT => 20));
            }
        }
    },
    []
);
