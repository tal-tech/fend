<?php

namespace Fend\Coroutine;

use Fend\Core\Context;
use Fend\Log\EagleEye;

/**
 * @method static defer(callable $callback)
 *
 * Class Coroutine
 * @package Fend\Coroutine
 */
class Coroutine
{
    private static $available;

    public static function isAvailable(): bool
    {
        return self::$available ?? (self::$available = class_exists(\Swoole\Coroutine::class));
    }

    /**
     * 获取当前协程id,兼容Swoole阻塞模式及fpm模式
     * @return int 非协程返回-1
     */
    public static function getCid(): int
    {
        if (self::isAvailable()) {
            return \Swoole\Coroutine::getCid();
        }
        return -1;
    }

    /**
     * 获取指定id协程的父协程id
     * @param int $cid 可选，要查询的协程id
     * @return int|null|false  -1非嵌套协程; false 非协程环境 ; 大于0 为父协程id
     */
    public static function getPcid(int $cid = 0)
    {
        if (self::inCoroutine()) {
            return \Swoole\Coroutine::getPcid($cid);
        }
        return -1;
    }

    /**
     * 获取一个协程的根协程id
     * @param $id
     * @return int|null 返回值：-1 当前没在协程内，大于0数值为root id(如果是第一层那么就是自身cid)
     */
    public static function getRootId(int $id): ?int
    {
        $pid = $id;

        if (self::inCoroutine()) {
            while (1) {
                $cid = \Swoole\Coroutine::getPcid($pid);
                if ($cid === -1 || $cid === false) {
                    return $pid;
                }
                $pid = $cid;
            }
        }

        return -1;
    }

    /**
     * 判断当前是否在协程
     * @return bool true在协程下
     */
    public static function inCoroutine(): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        return self::getCid() > 0;
    }

    public static function __callStatic($name, $arguments)
    {
        try {
            \Swoole\Coroutine::$name(...$arguments);
        } catch (\Error $throwable) {
            throw new \BadMethodCallException($throwable->getMessage());
        }
    }

    public static function create(callable $callable, ...$args): int
    {
        if (self::inCoroutine()) {
            $nextRpcId = EagleEye::getNextRpcId();

            $coroutineId = \Swoole\Coroutine::create(function () use ($callable, $args, $nextRpcId) {
                $pcid = Coroutine::getPcid();
                Context::copy($pcid);

                //set eagle eye rpcId
                Context::set(EagleEye::FIELD_RPC_ID, $nextRpcId);
                Context::set(EagleEye::FIELD_RPC_ID_SEQ, 1);

                $callable(...$args);
            });
            return is_int($coroutineId) ? $coroutineId : -1;
        }
        $callable(...$args);
        return -1;
    }

    /**
     * @param array $tasks
     * @param float $timeout
     * @return array|bool
     */
    public static function multi(array $tasks, float $timeout = -1): array
    {
        $count = count($tasks);
        if ($count === 0) {
            return [];
        }
        if (self::inCoroutine()) {
            $wg = new \Swoole\Coroutine\WaitGroup();
            $wg->add($count);
            foreach ($tasks as $key => $task) {
                $nextRpcId = EagleEye::getNextRpcId();
                \Swoole\Coroutine::create(function () use ($wg, &$tasks, $key, $task, $nextRpcId) {
                    $tasks[$key] = null;

                    $pcid = Coroutine::getPcid();
                    Context::copy($pcid);

                    //set eagle eye rpcId
                    Context::set(EagleEye::FIELD_RPC_ID, $nextRpcId);
                    Context::set(EagleEye::FIELD_RPC_ID_SEQ, 1);

                    $tasks[$key] = $task();

                    $wg->done();
                });
            }
            $wg->wait($timeout);
        } else {
            foreach ($tasks as &$task) {
                $task = $task();
            }
        }

        return $tasks;
    }
}
