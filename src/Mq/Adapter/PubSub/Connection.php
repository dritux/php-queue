<?php
namespace Dr\Mq\Adapter\PubSub;

use Google\Cloud\PubSub\PubSubClient;

class Connection
{

    protected $connection;

    public function __construct($projectId)
    {
        $this->connection = new PubSubClient([
            'projectId' => $projectId,
        ]);
    }
    /**
     * @return Connection
     */
    public function connect()
    {
        return $this->connection;
    }
}
