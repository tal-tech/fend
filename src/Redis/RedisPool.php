<?php
namespace Fend\Redis;

use Fend\Cache\Redis;
use Fend\Config;
use Fend\Exception\FendException;
use Fend\Pool\Pool;

class RedisPool extends Pool
{
    private static $instance;

    /**
     * @param string $name
     * @return mixed
     * @throws FendException
     */
    public static function factory(string $name)
    {
        if(!isset(static::$instance[$name])) {
            static::$instance[$name] = new static($name);
        }
        return static::$instance[$name];
    }

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $config;

    /**
     * RedisPool constructor.
     * @param string $name
     * @throws FendException
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $key = sprintf('Redis.%s', $this->name);

        try {
            $config = Config::get($key);
        } catch (\Exception $e) {
            throw new FendException(sprintf('config[%s] is not exist!', $key));
        }

        $this->config = $config['pool'] ?? [];

        parent::__construct($config);
    }

    /**
     * @return Redis
     * @throws FendException
     */
    protected function createConnection()
    {
        return new Redis($this->name);
    }

    /**
     * @return Redis
     * @throws FendException
     */
    public function get()
    {
        return $this->getConnection();
    }
}

