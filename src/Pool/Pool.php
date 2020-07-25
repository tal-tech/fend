<?php
namespace Fend\Pool;

use Fend\Coroutine\Channel;
use Fend\Exception\FendException;

abstract class Pool
{
    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var array
     */
    protected $option;

    /**
     * @var int
     */
    protected $currentConnections = 0;

    public function __construct(array $config = [])
    {
        $this->initOption($config);
        $this->channel = new Channel($this->option['max_connections']);
    }


    public function release($connection): void
    {
        $this->channel->push($connection);
    }

    public function getOption()
    {
        return $this->option;
    }

    protected function size(): int
    {
        return $this->channel->length();
    }

    /**
     * @return mixed
     * @throws FendException
     */
    protected function getConnection()
    {
        $num = $this->size();

        try {
            if ($num === 0 && $this->currentConnections < $this->option['max_connections']) {
                ++$this->currentConnections;
                return $this->createConnection();
            }
        } catch (FendException $throwable) {
            --$this->currentConnections;
            throw $throwable;
        }

        $connection = $this->channel->pop($this->option['wait_timeout']);
        if (!$connection) {
            throw new FendException('Cannot pop the connection, pop timeout.');
        }
        return $connection;
    }

    protected function initOption(array $options = []): void
    {
        $this->option = [
            'max_connections' => $options['max_connections'] ?? 10,
            'wait_timeout' => $options['wait_timeout'] ?? 3.0,
        ];
    }

    /**
     * @return mixed
     * @throws FendException
     */
    abstract protected function createConnection();

    abstract public function get();
}