<?php

namespace Fend\Core;

use Fend\Coroutine\Coroutine;
use Fend\Funcs\FendArray;
use Swoole\Coroutine as SwCoroutine;

class Context
{

    const _GLOBAL = '__GLOBAL';

    //FPM 下就一个Context
    protected static $nonCoContext = [
        "__GLOBAL" => []
    ];

    /**
     * 设置Context Global可继承变量
     * 通过Fend自带的coroutine创建的协程都会拷贝一份
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public static function setGlobal(string $key, $value)
    {
        if (Coroutine::inCoroutine()) {
            if (!isset(SwCoroutine::getContext()[self::_GLOBAL])) {
                SwCoroutine::getContext()[self::_GLOBAL] = [];
            }

            $context = SwCoroutine::getContext();
            FendArray::setByKey($context[self::_GLOBAL], $key, $value);
        } else {
            FendArray::setByKey(static::$nonCoContext[self::_GLOBAL], $key, $value);
        }
        return $value;
    }

    /**
     * 获取Context Global变量
     * @param string $key
     * @param null $default 获取不到返回的默认值
     * @return array|mixed|null
     */
    public static function getGlobal(string $key, $default = null)
    {
        if (Coroutine::inCoroutine()) {
            if (!isset(SwCoroutine::getContext()[self::_GLOBAL])) {
                return $default;
            }

            $context = SwCoroutine::getContext()[self::_GLOBAL];
            return FendArray::getByKey($context, $key, $default);
        } else {
            return FendArray::getByKey(static::$nonCoContext[self::_GLOBAL], $key, $default);
        }
    }

    /**
     * 设置Context kv
     * @param string $key
     * @param $value
     * @return mixed $value
     */
    public static function set(string $key, $value)
    {
        if (Coroutine::inCoroutine()) {
            $context = SwCoroutine::getContext();
            FendArray::setByKey($context, $key, $value);
        } else {
            FendArray::setByKey(static::$nonCoContext, $key, $value);
        }
        return $value;
    }

    /**
     * 获取Context kv
     * @param string $key
     * @param mixed $default 如果获取不到返回的默认值
     * @param mixed $coroutineId 获取指定coroutine id的context
     * @return array|mixed|null
     */
    public static function get(string $key, $default = null, $coroutineId = null)
    {
        if (Coroutine::inCoroutine()) {
            if ($coroutineId !== null) {
                return FendArray::getByKey(SwCoroutine::getContext($coroutineId), $key, $default);
            }
            return FendArray::getByKey(SwCoroutine::getContext(), $key, $default);
        }

        return FendArray::getByKey(static::$nonCoContext, $key, $default);
    }

    /**
     * 确认Context key是否存在
     * @param string $key
     * @param null $coroutineId
     * @return array|mixed|null
     */
    public static function has(string $key, $coroutineId = null)
    {
        if (Coroutine::inCoroutine()) {
            if ($coroutineId !== null) {
                return FendArray::hasByKey(SwCoroutine::getContext($coroutineId), $key);
            }
            return FendArray::hasByKey(SwCoroutine::getContext(), $key);
        }

        return FendArray::hasByKey(static::$nonCoContext, $key);
    }

    /**
     * Release the context when you are not in coroutine environment.
     * @param string $id
     */
    public static function destroy(?string $id = null)
    {
        if (!empty($id)) {
            unset(static::$nonCoContext[$id]);
        } else {
            static::$nonCoContext = [];
        }
    }

    /**
     * Copy the context from a coroutine to current coroutine.
     * @param int $fromCoroutineId
     */
    public static function copy(int $fromCoroutineId): void
    {
        /**
         * @var \ArrayObject
         * @var \ArrayObject $current
         */
        $from = SwCoroutine::getContext($fromCoroutineId);
        $current = SwCoroutine::getContext();
        $current->exchangeArray($from[self::_GLOBAL] ?? []);
    }

    /**
     * Retrieve the value and override it by closure.
     * @param string $id
     * @param \Closure $closure
     * @return mixed|null
     */
    public static function override(string $id, \Closure $closure)
    {
        $value = null;
        if (self::has($id)) {
            $value = self::get($id);
        }
        $value = $closure($value);
        self::set($id, $value);
        return $value;
    }

    /**
     * Retrieve the value and store it if not exists.
     * @param string $id
     * @param mixed $value
     * @return mixed|null
     */
    public static function getOrSet(string $id, $value)
    {
        if (!self::has($id)) {
            return self::set($id, $value);
        }
        return self::get($id);
    }

    public static function getContainer()
    {
        if (Coroutine::inCoroutine()) {
            return SwCoroutine::getContext();
        }

        return self::$nonCoContext;
    }
}
