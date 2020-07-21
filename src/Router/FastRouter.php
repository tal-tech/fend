<?php



namespace Fend\Router;


use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Fend\Di;
use function FastRoute\cachedDispatcher;

class FastRouter extends Router
{
    /**
     * @var self
     */
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @var Dispatcher[]
     */
    protected $dispatcher = [];

    /**
     * @param $config
     * @param $domain
     * @return $this
     */
    public function initRouter($config, $domain)
    {
        parent::initRouter($config, $domain);

        $openCache = $config['open_cache'] ?? true;

        $this->dispatcher[$domain] = cachedDispatcher(function (RouteCollector $routerCollector) use ($config, $domain) {
            foreach ($config["router"] as $routerDefine) {
                $routerCollector->addRoute($routerDefine[0], $routerDefine[1], $routerDefine[2]);
            }
        }, [
            'cacheFile' => SYS_CACHE . 'route.' . $domain . '.cache',
            'cacheDisabled' => !$openCache,
        ]);

        return $this;
    }

    /**
     * 根据method及uri调用对应配置的类
     * @param string $domain 域名
     * @param string $httpMethod post get other
     * @param string $uri 请求网址
     * @return mixed
     * @throws RouterException
     * @throws \ReflectionException
     */
    public function dispatch($domain, $httpMethod, $uri)
    {
        $uri = str_replace(array("//", "///", "////"), "", $uri);

        $config = [];
        //default router config
        if(!isset($this->dispatcher[$domain])) {
            $dispatcher = $this->dispatcher["default"];
            $config = $this->config['default'];
        }else {
            $dispatcher = $this->dispatcher[$domain];
            $config = $this->config[$domain];
        }

        //解析路由
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        //result status decide
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND: {
                // 路由未找到
                throw new RouterException("404 " . $uri. " map not found", 404);
            }
            case Dispatcher::METHOD_NOT_ALLOWED: {
                //$allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                throw new RouterException("405 Method Not Allowed", 405);
            }
            default: {
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                //设置网址内包含的参数
                Di::factory()->set("urlParam", $vars);

                //string rule is controllerName@functionName
                if (is_string($handler)) {
                    //decode handle setting
                    $handler = explode("@", $handler);
                    if (count($handler) != 2) {
                        throw new RouterException("Router Config error on handle.Handle only support two parameter with @" . $uri, -105);
                    }

                    $className = $handler[0];
                    $func = $handler[1];
                    //class check
                    if (!class_exists($className)) {
                        throw new RouterException("Router $uri Handle definded Class Not Found", -106);
                    }

                    //method check
                    if (!method_exists($className, $func)) {
                        throw new RouterException("Router $uri Handle definded $func Method Not Found", -107);
                    }

                    return $this->invokeFunc($className, $func, $config['middlewares'] ?? []);
                } else if (is_callable($handler)) {
                    //call direct when router define an callable function
                    return call_user_func_array($handler, []);
                } else {
                    throw new RouterException("Router Config error on handle." . $uri, -108);
                }
            }
        }
    }
}