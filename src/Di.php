<?php

namespace Fend;

use Fend\Core\RequestContext;
use Psr\Container\ContainerInterface;

/**
 * 全局变量管理
 * User: gary
 * Date: 2017/11/20
 * Time: 下午5:49
 */
class Di implements ContainerInterface
{
    /**
     * @var Di
     */
    private static $instance = null;

    /**
     * @return Di
     */
    public static function factory()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected $container = [];

    /**
     * 存储对象或数据到di内
     * @param $key
     * @param $val
     */
    public function set($key, $val)
    {
        $this->container[$key] = $val;
    }

    //原来的set如果存在禁止设置
    //现在的set已经可以覆盖，这个函数就没用了
    //留着是为了兼容
    public function reSet($key, $val)
    {
        $this->container[$key] = $val;
    }

    /**
     * 获取全部di内容
     * @return array
     */
    public function getList()
    {
        return $this->container;
    }

    /**
     * 获取对象或数据
     * @param $key
     * @param $params
     * @return mixed|string
     */
    public function get($key, $params = [])
    {
        if(class_exists($key)) {
            if (is_callable($key . '::factory')) {
                return call_user_func($key . '::factory', ...$params);
            }
            if (is_callable($key . '::Factory')) {
                return call_user_func($key . '::Factory', ...$params);
            }
            return isset($this->container[$key]) ? $this->container[$key] : $this->container[$key] = $this->make($key, $params);
        } else {
            return isset($this->container[$key]) ? $this->container[$key] : '';
        }
    }

    public function make($key, $params = [])
    {
        if(class_exists($key)) {
            return new $key(...$params);
        } else {
            return $params;
        }
    }

    /**
     * 设置request对象
     * @param Request $request
     */
    public function setRequest(\Fend\Request $request)
    {
        RequestContext::set("__request", $request);
    }

    /**
     * 获取request对象
     * @return \Fend\Request
     */
    public function getRequest()
    {
        return RequestContext::get("__request");
    }

    /**
     * 设置swoole table 句柄
     * @param string $key 表名
     * @param \Fend\Server\Table $table
     */
    public function setTable($key, $table)
    {
        $this->container["table"][$key] = $table;
    }

    /**
     * 获取swoole table
     * @param string $key
     * @return bool|\Fend\Server\Table
     */
    public function getTable($key)
    {
        if(isset($this->container["table"][$key])) {
            return $this->container["table"][$key];
        }
        return false;
    }

    /**
     * 获取所有swoole table列表
     * @return mixed
     */
    public function getTableList()
    {
        return $this->container["table"];
    }

    /**
     * set Response (typo backward compatibility)
     * @param \Fend\Response $response
     */
    public function setResonse(\Fend\Response $response)
    {
        RequestContext::set("__response", $response);
    }

    /**
     * set Response
     * @param \Fend\Response $response
     */
    public function setResponse(\Fend\Response $response)
    {
        $this->container["response"] = $response;
    }

    /**
     * 获取response对象
     * @return \Fend\Response
     */
    public function getResponse()
    {
        return RequestContext::get("__response");
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->container[$id]);
    }
}
