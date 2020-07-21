<?php
namespace Fend;

/**
 * Fend Framework
**/
class Cache
{

    /**
     * redis cache
     */
    const CACHE_TYPE_REDIS = 1;

    /**
     * Memcache
     */
    const CACHE_TYPE_MEMCACHE = 3;

    /**
     * @param int $t 选择数据0-文件,1-Redis,2-swoole-table
     * @param string $db 选择数据库
     * @return \Redis|\Memcached|\Fend\Cache\Redis|\Fend\Cache\Memcache
     * @throws \Exception
     */
    public static function factory($t = self::CACHE_TYPE_REDIS, $db = "")
    {
        if ($t == self::CACHE_TYPE_REDIS) {
            return \Fend\Cache\Redis::Factory(!empty($db) ? $db : 'default');
        } elseif ($t == self::CACHE_TYPE_MEMCACHE) {
            return \Fend\Cache\Memcache::Factory($db);
        } else {
            throw new \Exception("fend cache 传递未知cache类型");
        }
    }
}
