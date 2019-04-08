<?php
use Dr\Mq\MessageQueue;
use Dr\Mq\Adapter\PubSub\Connection;
use Dr\Mq\Adapter\PubSub\PubSub;
use Dr\Mq\Adapter\PubSub\Message;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/../../vendor/autoload.php';


$logger = new Logger('Subscriber');
$logger->pushHandler(new StreamHandler('/var/log/php-queue/publish.log'));


$adapter = new PubSub(
    new Connection("poc-pubsub-b1981"),
    "php-backend"
);

$mq = new MessageQueue($adapter);
$mq->setLogger($logger)->connect();


$message = new Message("sale-subscription");
$message
    ->setBody([
        "id"=>7,
        "client_id"=>3,
        "products"=>[2,3,3,4,5]
    ])
    ->setAttribute('uuid', uniqid())
    ->setAttribute('message_id', '55954976161');

$response = $mq->publish($message);
print_r($response);
