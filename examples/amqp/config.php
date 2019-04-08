<?php
class Environment
{
    const RABBITMQ_HOST='rabbitmq';
    const RABBITMQ_PORT = '5672';
    const RABBITMQ_USER = 'guest';
    const RABBITMQ_PASSWORD = 'guest';
    const RABBITMQ_VHOST = '/';
    const RABBITMQ_DEFAULT_INSIST = false;
    const RABBITMQ_DEFAULT_LOGIN_METHOD = 'AMQPLAIN';
    const RABBITMQ_DEFAULT_LOGIN_RESPONSE = null;
    const RABBITMQ_DEFAULT_LOCALE = 'en_US';
    const RABBITMQ_DEFAULT_CONNECTION_TIMEOUT = 32.0;
    const RABBITMQ_DEFAULT_READ_WRITE_TIMEOUT = 32.0;
    const RABBITMQ_DEFAULT_CONTEXT = null;
    const RABBITMQ_DEFAULT_KEEPALIVE = false;
    const RABBITMQ_DEFAULT_HEARTBEAT = 16;
}
