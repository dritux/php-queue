<?php
namespace Dr\Mq\Adapter\Amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class Connection
{

    protected $connection;

    /**
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $password
     * @param string $vhost
     * @param bool $insist
     * @param string $login_method
     * @param null $login_response
     * @param string $locale
     * @param float $connection_timeout
     * @param float $read_write_timeout
     * @param null $context
     * @param bool $keepalive
     * @param int $heartbeat
     */
    public function __construct(
        $host,
        $port,
        $user,
        $password,
        $vhost = '/',
        $insist = false,
        $login_method = 'AMQPLAIN',
        $login_response = null,
        $locale = 'en_US',
        $connection_timeout = 3.0,
        $read_write_timeout = 3.0,
        $context = null,
        $keepalive = false,
        $heartbeat = 0
    ) {
        $this->connection = new AMQPStreamConnection(
            $host,
            $port,
            $user,
            $password,
            $vhost,
            $insist,
            $login_method,
            $login_response,
            $locale,
            $connection_timeout,
            $read_write_timeout,
            $context,
            $keepalive,
            $heartbeat
        );
    }

    /**
     * @return Connection
     */
    public function connect() {
        return $this->connection;
    }

    /**
     * @return Channel
     */
    public function channel($channel_id = null)
    {
        return $this->connection->channel();
    }
}
