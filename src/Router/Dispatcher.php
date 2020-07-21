<?php



namespace Fend\Router;

use Fend\Config;

class Dispatcher
{
    protected $config = array();

    protected $router;

    /**
     * 初始化路由类，并加载路由配置
     * Router constructor.
     * @param array $config
     * @throws RouterException 配置获取错误会抛异常
     */
    public function __construct(array $config = [])
    {
        //record routerConfig
        $this->config = $config;
        if (empty($this->config)) {
            $this->config = Config::get('Router');
        }

        //init router by Config
        foreach ($this->config['map'] as $domain => $config) {
            if (!$config["direct"] && !$config["fastrouter"]) {
                throw new RouterException("Router 配置错误！Domain:" . $domain . " direct及fastrouter至少开启一个", -2355);
            }

            $config["root"]       = rtrim($config["root"], "\\");

            if ($config["fastrouter"]) {
                $this->router[$domain][] = FastRouter::instance()->initRouter($config, $domain);
            }

            if ($config["direct"]) {
                $this->router[$domain][] = DefaultRouter::instance()->initRouter($config, $domain);
            }
        }
    }

    public function dispatch($domain, $httpMethod, $uri)
    {
        if (!isset($this->router[$domain])) {
            $routers = $this->router['default'];
        } else {
            $routers = $this->router[$domain];
        }
        foreach ($routers as $router) {
            try {
                /**
                 * @var $router Router
                 */
                $result = $router->dispatch($domain, $httpMethod, $uri);
                return $result;
            } catch (RouterException $e) {
                if ($e->getCode() == '404') {
                    continue;
                } else {
                    throw $e;
                }
            }
        }
        //找不到了
        throw new RouterException("404 " . $uri . " map not found", 404);
    }
}