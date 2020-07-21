<?php

namespace Fend\App;

use Fend\Cache;

/**
 * Class RedisModel
 * @package Fend\App
 */
class RedisModel extends \Fend\Fend
{

    /**
     * @var \Redis
     */
    protected $_db = null;

    protected $_config = "default";

    protected $_dbType = Cache::CACHE_TYPE_REDIS;

    /**
     * RedisModel constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->_db = Cache::factory($this->_dbType, $this->_config);
    }

    /**
     * 开启批量执行模式，支持服务器端"锁表"连续执行
     * @param bool $isLock false：默认值，使用pipline模式，不锁定redis批量执行命令 | true：使用multi模式会锁定服务器执行独立执行
     */
    public function startTransaction($isLock = false)
    {
        if ($isLock) {
            $this->_db->multi(\Redis::MULTI);
        } else {
            $this->_db->multi(\Redis::PIPELINE);
        }
    }

    /**
     * 提交执行返回执行结果
     * @return array
     */
    public function commitTransaction()
    {
        return $this->_db->exec();
    }

    /**
     * 取消之前提交
     */
    public function cancelTransaction()
    {
        $this->_db->discard();
    }

    /**
     * 队列：插入
     * 注意：此功能为简略版的队列，使用list直接push、pop，不能保证数据完整性，需要业务自行完善
     * @param string $key
     * @param string $data
     * @return bool|int
     */
    public function pushQueue($key, $data)
    {
        return $this->_db->lPush($key, $data);
    }

    /**
     * 队列：获取任务
     * @param string $key 队列key
     * @param int $timeout 超时时间默认0，如果设置会阻塞等待数据指定时间，有数据马上返回，超过时间返回false
     * @return array|string|false 返回数据，没有数据返回false
     */
    public function popQueue($key, $timeout = 0)
    {
        if ($timeout === 0) {
            return $this->_db->rPop($key);
        } else {
            return $this->_db->brPop($key, $timeout);
        }
    }

    /**
     * 闭包方式实现redis互斥锁，如果抢锁失败返回false
     * @param callable $fun 闭包函数
     * @param string $key 锁名称
     * @param int $timeout 锁超时时间,超过时间锁自动失效
     * @return mixed 成功时返回闭包函数的返回值
     * @throws \Exception 失败时抛出异常信息
     */
    public function locked(callable $fun, $key, $timeout = 30)
    {
        $locked = $this->lock($key, $timeout);
        if(false === $locked) throw new \Exception("Failed to grab redis lock",-1);

        try{
            $result = call_user_func($fun);
            $this->unlock($key);
            return $result;
        }catch (\Exception $e){
            $this->unlock($key);
            throw new \Exception($e->getMessage(),-2);
        }
    }

    /**
     * 通过redis实现的互斥锁，上锁，如果失败返回false
     * @param string $key 锁名称
     * @param int $timeout 锁超时时间,超过时间锁自动失效
     * @return bool 成功返回true，失败返回false
     */
    public function lock($key, $timeout = 30)
    {
        return $this->_db->setex("lock_" . $key, $timeout, 1);
    }

    /**
     * 通过redis实现的互斥锁，解锁
     * @param string $key 锁名称
     * @return bool|int 失败返回false
     */
    public function unlock($key)
    {
        return $this->_db->del("lock_" . $key);
    }

    /**
     * 批量获取key
     * @param array $keys 批量获取string key
     * @return array
     */
    public function mGet($keys)
    {
        return $this->_db->mget($keys);
    }

    /**
     * 批量设置string
     * @param array $data
     * @return bool
     */
    public function mSet($data)
    {
        return $this->_db->mset($data);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->_db, $name), $arguments);
    }

    /**
     * 缓存模板函数
     * 有效减少重复代码
     * ```
     * $data = $cache->getOrSet('key', function() {
     *   // 查询数据库或其他操作...
     *   return ['userId' => 1];
     * }, 3600);
     * var_dump($data); // ['userId' => 1]
     * ```
     * @param string $key 缓存key
     * @param callable $callable 缓存失效时调用的函数，并将该函数返回值进行缓存
     * @param int $expire
     * @return mixed|string
     * @throws \Exception
     * @author xialeistudio
     * @date 2019-09-05
     */
    public function getOrSet($key, callable $callable, $expire = 0)
    {
        return $this->_db->getOrSet($key, $callable, $expire);
    }
}
