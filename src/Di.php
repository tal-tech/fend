<?php

namespace Fend;

/**
 * 全局变量管理
 * User: gary
 * Date: 2017/11/20
 * Time: 下午5:49
 */
class Di
{
    protected $container = array();

    public static function factory()
    {
        static $_obj = null;
        //是否需要重新连接
        if (empty($_obj)) {
            $_obj = new self();
        }
        return $_obj;
    }

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
        return isset($this->container[$key]) ? $this->container[$key] : '';
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
        $this->container["request"] = $request;
    }

    /**
     * 获取request对象
     * @return \Fend\Request
     */
    public function getRequest()
    {
        return $this->container["request"];
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
        return $this->container["response"];
    }
}
