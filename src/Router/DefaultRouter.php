<?php



namespace Fend\Router;


class DefaultRouter extends Router
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

    public function dispatch($domain, $httpMethod, $uri)
    {
        $config = [];
        //域名查找配置
        if (isset($this->config[$domain])) {
            $root = $this->config[$domain]['root'];
            $config = $this->config[$domain];
        } else if (isset($this->config['default'])) {
            $root = $this->config['default']['root'];
            $config = $this->config['default'];
        } else {
            throw new RouterException('router has no default Config for router', -2356);
        }

        $uri = strtolower(trim($uri, "/"));
        $uri = ucwords($uri, "/");
        $uri = explode("/", $uri);
        //uri为空，那么默认index
        if (empty($uri) || count($uri) == 1 && $uri[0] === "") {
            $className = $root . "\\Index";

            if (class_exists($className) && method_exists($className, "index")) {
                return $this->invokeFunc($className, "index", $config['middlewares'] ?? []);
            }
            //找不到404
            throw new RouterException("Default Router index/index Handle define Class Not Found", 404);
        }

        //尝试uri为class名称
        //查找index执行
        //$className = $className . "\\" . $function;
        $className = $root . "\\" . implode("\\", $uri);
        $function  = "index";

        if (class_exists($className)) {
            //并且最后一个是function name
            $function = $this->methodExists($className, $function);
            if (!empty($function)) {
                return $this->invokeFunc($className, $function, $config['middlewares'] ?? []);
            }
        }

        //判断最后uri是否为function,前面作为类路径
        //检测一次

        $function  = array_pop($uri);
        $className = $root . "\\" . implode("\\", $uri);

        //前面是否为合法类路径
        if (class_exists($className)) {
            //并且最后一个是function name
            $function = $this->methodExists($className, $function);
            if (!empty($function)) {
                return $this->invokeFunc($className, $function, $config['middlewares'] ?? []);
            }
        }

        //找不到了
        throw new RouterException("404 " . implode("\\", $uri) . " map not found", 404);
    }
}