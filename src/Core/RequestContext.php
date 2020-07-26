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
    protected static $rootIdMap = [];

    public static function set(string $key, $value)
    {
        $cid = Coroutine::getCid();

        if ($cid !== -1) {
            $rootId = self::getRootId($cid);
            if (!isset(self::$Context[$rootId])) {
                self::$Context[$rootId] = [];
            }
            FendArray::setByKey(self::$Context[$rootId], $key, $value);
        } else {
            FendArray::setByKey(self::$Context[-1], $key, $value);
        }
        return $value;
    }

    public static function setMulti($data)
    {
        $cid = Coroutine::getCid();

        if ($cid !== -1) {
            $rootId = self::getRootId($cid);
            if (!isset(self::$Context[$rootId])) {
                self::$Context[$rootId] = [];
            }
            foreach ($data as $key => $val) {
                FendArray::setByKey(self::$Context[$rootId], $key, $val);
            }
        } else {
            foreach ($data as $key => $val) {
                FendArray::setByKey(self::$Context[-1], $key, $val);
            }
        }
        return $data;
    }

    public static function get(string $key, $default = null)
    {
        $cid = Coroutine::getCid();

        if ($cid !== -1) {
            $rootId = self::getRootId($cid);
            if (!isset(static::$Context[$rootId])) {
                static::$Context[$rootId] = [];
            }
            return FendArray::getByKey(static::$Context[$rootId], $key, $default);
        }

        return FendArray::getByKey(static::$Context[-1], $key, $default);
    }

    public static function getMulti($data)
    {
        $result = [];
        $cid = Coroutine::getCid();

        if ($cid !== -1) {
            $rootId = self::getRootId($cid);
            if (!isset(static::$Context[$rootId])) {
                static::$Context[$rootId] = [];
            }
            foreach ($data as $key => $item) {
                $result[$item["key"] ?? $key] = FendArray::getByKey(static::$Context[$rootId], $key, $item["default"] ?? null);
            }
            return $result;
        }

        foreach ($data as $key => $realKey) {
            $result[$item["key"] ?? $key] = FendArray::getByKey(static::$Context[-1], $key, $item["default"] ?? null);
        }
        return $result;
    }

    public static function has(string $key)
    {
        $cid = Coroutine::getCid();

        if ($cid !== -1) {
            $rootId = self::getRootId($cid);
            if (!isset(static::$Context[$rootId])) {
                return false;
            }
            return FendArray::hasByKey(static::$Context[$rootId], $key);
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
        self::$rootIdMap[self::$rootId[$cid]][$cid] = 1;
        return self::$rootId[$cid];
    }

    /**
     * 销毁当前RequestContext
     * @param string $key 如果指定key只是requestContext删除指定key
     */
    public static function destroy(?string $key = null)
    {
        if (Coroutine::inCoroutine()) {
            $rootId = self::getRootId(Coroutine::getCid());

            if (!empty($key)) {
                unset(static::$Context[$rootId][$key]);
            } else {
                unset(static::$Context[$rootId]);
                if (isset(self::$rootIdMap[$rootId]) && is_array(self::$rootIdMap[$rootId])) {
                    foreach (self::$rootIdMap[$rootId] as $cidKey => $v) {
                        unset(self::$rootId[$cidKey]);
                    }
                    unset(self::$rootIdMap[$rootId]);
                }
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