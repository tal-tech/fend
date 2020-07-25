<?php
declare(strict_types=1);

namespace Fend\Core;

use Fend\Coroutine\Coroutine;
use Fend\Funcs\FendArray;

class RequestContext
{
    // 所有请求级别context 存储
    protected static $Context = [
        -1 => [],
    ];

    protected static $rootId = [];

    public static function set(string $key, $value)
    {
        if (Coroutine::inCoroutine()) {
            $cid = self::getRootId(Coroutine::getCid());
            if (!isset(self::$Context[$cid])) {
                self::$Context[$cid] = [];
            }
            FendArray::setByKey(self::$Context[$cid], $key, $value);
        } else {
            FendArray::setByKey(self::$Context[-1], $key, $value);
        }
        return $value;
    }

    public static function get(string $key, $default = null)
    {
        if (Coroutine::inCoroutine()) {
            $cid = self::getRootId(Coroutine::getCid());
            if (!isset(static::$Context[$cid])) {
                static::$Context[$cid] = [];
            }
            return FendArray::getByKey(static::$Context[$cid], $key, $default);
        }

        return FendArray::getByKey(static::$Context[-1], $key, $default);
    }

    public static function has(string $key)
    {
        if (Coroutine::inCoroutine()) {
            $cid = self::getRootId(Coroutine::getCid());
            if (!isset(static::$Context[$cid])) {
                return false;
            }
            return FendArray::hasByKey(static::$Context[$cid], $key);
        }

        return FendArray::hasByKey(static::$Context[-1], $key);
    }

    /**
     * Retrieve the value and override it by closure.
     * @param string $key
     * @param \Closure $closure
     * @return mixed|null
     */
    public static function override(string $key, \Closure $closure)
    {
        $value = null;
        if (self::has($key)) {
            $value = self::get($key);
        }
        $value = $closure($value);
        self::set($key, $value);
        return $value;
    }

    /**
     * 获取指定Cid对应的根Root Context
     * @param int $cid 协程id
     * @return int
     */
    public static function getRootId(int $cid)
    {
        if (isset(self::$rootId[$cid])) {
            return self::$rootId[$cid];
        }

        self::$rootId[$cid] = Coroutine::getRootId($cid);
        return self::$rootId[$cid];
    }

    /**
     * 销毁当前RequestContext
     * @param string $key 如果指定key只是requestContext删除指定key
     */
    public static function destroy(?string $key = null)
    {
        if (Coroutine::inCoroutine()) {
            $cid = self::getRootId(Coroutine::getCid());

            if (!empty($key)) {
                unset(static::$Context[$cid][$key]);
            } else {
                unset(static::$Context[$cid]);
                unset(self::$rootId[$cid]);
            }
            return;
        }
        if (!empty($key)) {
            unset(static::$Context[-1][$key]);
        } else {
            static::$Context[-1] = [];
        }
    }

}