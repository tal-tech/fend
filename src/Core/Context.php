<?php

namespace Fend\Core;

use Fend\Coroutine\Coroutine;
use Fend\Funcs\FendArray;
use Swoole\Coroutine as SwCoroutine;

class Context
{

    const _GLOBAL = '__GLOBAL';

    protected static $nonCoContext = [];

    public static function setGlobal(string $id, $value)
    {
        if (Coroutine::inCoroutine()) {
            if (!isset(SwCoroutine::getContext()[self::_GLOBAL])) {
                SwCoroutine::getContext()[self::_GLOBAL] = [];
            }

            $context = SwCoroutine::getContext();
            FendArray::setByKey($context[self::_GLOBAL], $id, $value);
        } else {
            if (!isset(static::$nonCoContext[self::_GLOBAL])) {
                static::$nonCoContext[self::_GLOBAL] = [];
            }
            FendArray::setByKey(static::$nonCoContext[self::_GLOBAL], $id, $value);
        }
        return $value;
    }

    public static function getGlobal(string $id, $default = null)
    {
        if (Coroutine::inCoroutine()) {
            if (!isset(SwCoroutine::getContext()[self::_GLOBAL])) {
                return $default;
            }

            $context = SwCoroutine::getContext()[self::_GLOBAL];
            return FendArray::getByKey($context, $id, $default);
        } else {
            if (!isset(static::$nonCoContext[self::_GLOBAL])) {
                return $default;
            }
            return FendArray::getByKey(static::$nonCoContext[self::_GLOBAL], $id, $default);
        }
    }

    public static function set(string $id, $value)
    {
        if (Coroutine::inCoroutine()) {
            $context = SwCoroutine::getContext();
            FendArray::setByKey($context, $id, $value);
        } else {
            FendArray::setByKey(static::$nonCoContext, $id, $value);
        }
        return $value;
    }

    public static function get(string $id, $default = null, $coroutineId = null)
    {
        if (Coroutine::inCoroutine()) {
            if ($coroutineId !== null) {
                return FendArray::getByKey(SwCoroutine::getContext($coroutineId), $id, $default);
            }
            return FendArray::getByKey(SwCoroutine::getContext(), $id, $default);
        }

        return FendArray::getByKey(static::$nonCoContext, $id, $default);
    }

    public static function has(string $id, $coroutineId = null)
    {
        if (Coroutine::inCoroutine()) {
            if ($coroutineId !== null) {
                return FendArray::hasByKey(SwCoroutine::getContext($coroutineId), $id);
            }
            return FendArray::hasByKey(SwCoroutine::getContext(), $id);
        }

        return FendArray::hasByKey(static::$nonCoContext, $id);
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
        if (! self::has($id)) {
            return self::set($id, $value);
        }
        return self::get($id);
    }

    public static function getContainer()
    {
        if (Coroutine::inCoroutine()) {
            return SwCoroutine::getContext();
        }

        return static::$nonCoContext;
    }
}
