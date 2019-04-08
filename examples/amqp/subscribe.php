<?php
use Dr\Mq\MessageQueue;
use Dr\Mq\Adapter\Amqp\Amqp;
use Dr\Mq\Adapter\Amqp\Message;

use Dr\Mq\Adapter\Amqp\Connection;
use Dr\Mq\EnvelopeInterface;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'config.php';

$env = new Environment();
$queue = "dr:test:queue";
$consumer_id = "consumer_id";

$adapter = new Amqp(
    new Connection(
    $env::RABBITMQ_HOST,
    $env::RABBITMQ_PORT,
    $env::RABBITMQ_USER,
    $env::RABBITMQ_PASSWORD,
    $env::RABBITMQ_VHOST,
    $env::RABBITMQ_DEFAULT_INSIST,
    $env::RABBITMQ_DEFAULT_LOGIN_METHOD,
    $env::RABBITMQ_DEFAULT_LOGIN_RESPONSE,
    $env::RABBITMQ_DEFAULT_LOCALE,
    $env::RABBITMQ_DEFAULT_CONNECTION_TIMEOUT,
    $env::RABBITMQ_DEFAULT_READ_WRITE_TIMEOUT,
    $env::RABBITMQ_DEFAULT_CONTEXT,
    $env::RABBITMQ_DEFAULT_KEEPALIVE,
    $env::RABBITMQ_DEFAULT_HEARTBEAT),
    $consumer_id
);

$mq = new MessageQueue($adapter);
$mq->connect();

$subscription = $mq->subscribe(
    $queue,
    function (EnvelopeInterface $message, MessageQueue $mq) {

        $payload = $message->getBody();

        if (empty($payload)) {
            return false;
        }
        print_r($payload);

        $mq->ack($message);
    },
    []
);
