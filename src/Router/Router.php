<?php

namespace Fend\Router;

use Fend\Di;
use Fend\Request;
use Fend\Exception\ExitException;
use Fend\Router\Middleware\RequestHandler;
use Fend\Router\Middleware\HttpRequestHandler;

/**
 * 请求路由解析
 * 内部使用fastrouter方式
 * Class Router
 * @package Fend
 */
abstract class Router
{
    protected $dispatcherHandle = [];

    protected $config = [];

    /**
     * 调用系统函数
     * @param $className
     * @param $function
     * @param array $middlewares
     * @return string
     * @throws \Fend\Exception\FendException
     */
    protected function invokeFunc($className, $function, array $middlewares)
    {
        $request = Di::factory()->getRequest();

        method_exists($request, "setController") && $request->setController($className, $function);

        $requestHandler = new HttpRequestHandler($middlewares, $this);

        return $requestHandler->handle($request);
    }

    /**
     * @param $request
     * @param RequestHandler $handler
     * @return string
     * @throws \ReflectionException
     */
    public function process($request, RequestHandler $handler)
    {
        $response = Di::factory()->getResponse();

        /**
         * @var $request Request
         */
        $info = $request->getController();
        $className = $info['controller'];
        $function = $info['action'];

        $controller = new $className();

        //init
        if (method_exists($controller, "Init")) {
            if($this->getNumOfParams([$className,"Init"]) == 2){
                $controller->Init($request, $response);
            }else{
                $controller->Init();
            }
        }

        try{
            if($this->getNumOfParams([$controller, $function]) == 2){
                $result = $controller->$function($request, $response);
            }else{
                $result = $controller->$function();
            }
        }catch (ExitException $e){
            //do nothing continue run the un init
            $result = $e->getData();
        }

        //UnInit
        if (method_exists($controller, "UnInit")) {
            if($this->getNumOfParams([$controller,"UnInit"]) == 3){
                $controller->UnInit($request, $response, $result);
            }else{
                $controller->UnInit();
            }
        }

        return $result;
    }

    protected function methodExists($className, $method)
    {
        $methods = get_class_methods($className);
        foreach ($methods as $methodName)
        {
            if(strtolower($method) == strtolower($methodName))
            {
                return $methodName;
            }
        }
        return "";
    }

    /**
     * 通过反射判断被调用函数参数个数
     * @param $callable
     * @return int
     * @throws \ReflectionException
     */
    private function getNumOfParams($callable)
    {
        $CReflection = is_array($callable) ? new \ReflectionMethod($callable[0], $callable[1]) : new \ReflectionFunction($callable);
        return $CReflection->getNumberOfParameters();
    }

    /**
     * @param $config
     * @param $domain
     * @return $this
     */
    public function initRouter($config, $domain)
    {
        $this->config[$domain] = $config;
        return $this;
    }

    /**
     * @param $domain
     * @param $httpMethod
     * @param $uri
     * @return bool
     * @throws RouterException
     */
    abstract function dispatch($domain, $httpMethod, $uri);
}