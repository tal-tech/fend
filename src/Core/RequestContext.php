<?php
declare(strict_types=1);


namespace Fend\Core;


use Fend\Coroutine\Coroutine;
use Fend\Funcs\FendArray;

class RequestContext
{
    protected static $Context = [
        -1 => [],
    ];

    public static function set(string $id, $value)
    {
        if (Coroutine::inCoroutine()) {
            $cid = Coroutine::getRootId(Coroutine::getCid());
            if(!isset(self::$Context[$cid])) {
                self::$Context[$cid] = [];
            }
            FendArray::setByKey(self::$Context[$cid], $id, $value);
        } else {
            FendArray::setByKey(self::$Context[-1], $id, $value);
        }
        return $value;
    }

    public static function get(string $id, $default = null)
    {
        if (Coroutine::inCoroutine()) {
            $cid = Coroutine::getRootId(Coroutine::getCid());
            if(!isset(static::$Context[$cid])) {
                static::$Context[$cid] = [];
            }
            return FendArray::getByKey(static::$Context[$cid], $id, $default);
        }

        return FendArray::getByKey(static::$Context[-1], $id, $default);
    }

    public static function has(string $id)
    {
        if (Coroutine::inCoroutine()) {
            $cid = Coroutine::getRootId(Coroutine::getCid());
            if(!isset(static::$Context[$cid])) {
                return false;
            }
            return FendArray::hasByKey(static::$Context[$cid], $id);
        }

        return FendArray::hasByKey(static::$Context[-1], $id);
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
     * @param string $id
     */
    public static function destroy(?string $id = null)
    {
        if (Coroutine::inCoroutine()) {
            $cid = Coroutine::getRootId(Coroutine::getCid());

            if (!empty($id)) {
                unset(static::$Context[$cid][$id]);
            } else {
                unset(static::$Context[$cid]);
            }
            return;
        }
        if (!empty($id)) {
            unset(static::$Context[-1][$id]);
        } else {
            static::$Context[-1] = [];
        }
    }

}