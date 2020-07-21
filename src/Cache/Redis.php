<?php

namespace Fend\Cache;

use Fend\Config;
use Fend\Debug;
use Fend\Exception\SystemException;
use Fend\Log\EagleEye;

/**
 * Class Redis
 * @package Fend\Cache
 */
class Redis
{
    /**
     * @var static[]
     */
    private static $instance = [];

    private $config = null;

    private $dbName = "";

    /**
     * @var \Redis
     */
    private $redis = null;

    /**
     * @var int 最后一次ping时间
     */
    private $lastPingTime = 0;

    /**
     * @param string $db
     * @param string $hash
     * @return Redis
     * @throws \Exception
     */
    public static function Factory($db = 'default', $hash = '')
    {
        //全链路压测时，自动读写影子库
        $redisConfig = Config::get('Redis');
        if (EagleEye::getGrayStatus() && isset($redisConfig[$db . "-gray"])) {
            $db = $db . "-gray";
        }
        if (!isset(self::$instance[$db]) || self::$instance[$db] === null) {
            self::$instance[$db] = new static($db, $hash);
        }
        return self::$instance[$db];
    }

    /**
     * Redis constructor.
     * @param string $db
     * @param string $hash
     * @throws SystemException
     */
    public function __construct($db = "default", $hash = '')
    {
        $this->dbName = $db;

        $redisConfig = Config::get('Redis');

        if (!isset($redisConfig[$db])) {
            throw new SystemException("Redis Config not found", -6301);
        }

        $config       = $redisConfig[$db];
        $this->config = $config;

        //do connect
        $this->reconnect();
    }

    /**
     * 周期检测链接可用
     * @throws SystemException
     */
    public function checkConnection()
    {
        if ($this->lastPingTime + 1 <= time()) {

            try {
                if ($this->redis->ping() != "+PONG") {
                    $this->reconnect();
                }
            } catch (\Exception $e) {
                $this->reconnect();
            }

            $this->lastPingTime = time();
        }
    }

    /**
     * 重连
     * @throws SystemException
     */
    public function reconnect()
    {
        $retry = $this->config["retry"] ?? 6; // 默认6次
        $retryInterval = $this->config["retry_interval"] ?? 200000; // 默认间隔200毫秒

        //connect the server
        while ($retry) {
            $this->redis = new \Redis();
            try {
                $this->redis->connect($this->config["host"], $this->config["port"] ?? 6379, $this->config["timeout"] ?? 0);
                break;
            } catch (\RedisException $e) {
                $retry --;
                if (!$retry) {
                    throw new SystemException("connect Redis Server fail db:" . $this->dbName . " error:" . $this->redis->getLastError(), -24);
                }
                usleep($retryInterval);
                continue;
            }
        }

        $this->redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
        $this->redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);

        //prefix
        if (isset($this->config["pre"]) && !empty($this->config["pre"])) {
            $this->redis->setOption(\Redis::OPT_PREFIX, $this->config["pre"]);
        }

        //auth
        if (isset($this->config["pwd"]) && !empty($this->config["pwd"])) {
            if ($this->redis->auth($this->config["pwd"]) == FALSE) {
                throw new SystemException("Redis auth fail.dbname:" . $this->dbName, -23);
            }
        }

        //db
        if (isset($this->config["db"]) && !empty($this->config["db"])) {
            $this->redis->select($this->config["db"]);
        }
    }

    /**
     * 获取源对象
     * @return \Redis
     */
    public function getRedisObj()
    {
        return $this->redis;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws SystemException 链接异常
     */
    public function __call($name, $arguments)
    {
        //check is work well
        $this->checkConnection();

        $cost = microtime(true);
        try {
            //do the cmd，如果刚检测完还报错，那。。。再来一次吧
            $result = call_user_func_array(array($this->redis, $name), $arguments);
            if (Debug::isDebug()) {
                Debug::appendRedisInfo([
                    "mode" => $this->dbName,
                    "op" => json_encode([$name , $arguments]),
                    "cost" => round(microtime(true) - $cost, 4),
                    "result_len"  => strlen($result),
                ]);
            }
            return $result;
        } catch (\Exception $e) {
            $this->reconnect();
            $result = call_user_func_array(array($this->redis, $name), $arguments);
            if (Debug::isDebug()) {
                Debug::appendRedisInfo([
                    "mode" => $this->dbName,
                    "op" => json_encode([$name , $arguments]),
                    "cost" => round(microtime(true) - $cost, 4),
                    "result_len"  => strlen($result),
                ]);
            }
            return $result;
        }
    }

    /**
     * 格式化过期时间
     * 注意: 限制时间小于2592000=30天内
     *
     * @param string $t 要处理的串
     * @return int
     * @throws SystemException
     * */
    public function setLifeTime($t)
    {
        if (!is_numeric($t)) {
            switch (substr($t, -1)) {
                case 'w'://周
                    $t = (int)$t * 7 * 24 * 3600;
                    break;
                case 'd'://天
                    $t = (int)$t * 24 * 3600;
                    break;
                case 'h'://小时
                    $t = (int)$t * 3600;
                    break;
                case 'i'://分钟
                    $t = (int)$t * 60;
                    break;
                default:
                    throw new SystemException("set life time wrong " . $t, -6371);
            }
        }
        $t > 2592000 && $t = 2592000;
        return $t;
    }

}
