<?php

namespace Fend;

use Fend\Exception\FendException;

/**
 * Request封装
 * Class Request
 * @package Fend
 */
class Request
{

    private $_type = "fpm";

    private $_post = [];
    private $_get = [];
    private $_server = [];
    private $_cookie = [];
    private $_file = [];
    private $_header = [];
    private $_controller_name = "";
    private $_controller_action = "";

    //case insensitive key map
    private $_post_map = [];
    private $_get_map = [];
    private $_cookie_map = [];
    private $_file_map = [];

    /**
     * Request constructor.
     * @param string $type 可选项fpm,swoole_http
     * @throws \Exception
     */
    public function __construct($type = "fpm")
    {
        $this->_type = "$type";

        if ($type === "fpm") {
            $this->_post = $_POST ?? [];
            $this->_post_map = array_change_key_case($this->_post, CASE_UPPER);

            $this->_get = $_GET ?? [];
            $this->_get_map = array_change_key_case($this->_get, CASE_UPPER);

            $this->_server = $_SERVER ?? [];

            $this->_cookie = $_COOKIE ?? [];
            $this->_cookie_map = array_change_key_case($this->_cookie, CASE_UPPER);

            $this->_file = $_FILES ?? [];
            $this->_file_map = array_change_key_case($this->_file, CASE_UPPER);

            //header
            foreach ($_SERVER as $key => $val) {
                if (substr($key, 0, 5) === 'HTTP_') {
                    $key = substr($key, 5);
                    $key = str_replace('_', ' ', $key);
                    $key = str_replace(' ', '-', $key);
                    $key = strtoupper($key);
                    $this->_header[$key] = $val;
                }
            }
        } elseif ($type === "swoole_http") {
            /**
             * @var \Swoole\Http\Request $request
             */
            $request = Di::factory()->get("http_request");

            if (!$request) {
                throw new \Exception("swoole request 获取失败", 11);
            }

            $this->_post = $request->post ?? [];
            $this->_post_map = array_change_key_case($this->_post, CASE_UPPER);

            $this->_get = $request->get ?? [];
            $this->_get_map = array_change_key_case($this->_get, CASE_UPPER);

            $this->_server = array_change_key_case($request->server ?? [], CASE_UPPER);

            $this->_cookie = $request->cookie ?? [];
            $this->_cookie_map = array_change_key_case($this->_cookie, CASE_UPPER);

            $this->_file = $request->file ?? [];
            $this->_file_map = array_change_key_case($this->_file, CASE_UPPER);

            $this->_header = array_change_key_case($request->header ?? [], CASE_UPPER);

        } else {
            throw new \Exception("未知request类型", 12);
        }
    }

    /**
     * 获取post参数
     * @param string $name
     * @param mixed $default 默认值
     * @param string $filter 数据过滤 可选 html/string/special/url/email/float/int 不填直接返回
     * @return array|mixed|string
     */
    public function post($name = "", $default = "", $filter = "")
    {
        if ($name === '') {
            return $this->_post;
        }

        $name = strtoupper($name);

        if (!isset($this->_post_map[$name])) {
            return $default;
        }

        return $this->filter($this->_post_map[$name], $filter);
    }

    /**
     * 获取get参数
     * @param string $name
     * @param mixed $default 默认值
     * @param string $filter 数据过滤 可选 html/string/special/url/email/float/int 不填直接返回
     * @return array|mixed|string
     */
    public function get($name = "", $default = "", $filter = "")
    {
        if ($name === '') {
            return $this->_get;
        }

        $name = strtoupper($name);

        if (!isset($this->_get_map[$name])) {
            return $default;
        }

        return $this->filter($this->_get_map[$name], $filter);
    }

    /**
     * 获取参数，post优先获取，获取不到从querystring获取
     * @param string $key 要获取的key
     * @param mixed $default 如果都没有获取到，使用的默认值
     * @param string $filter 数据过滤 可选 html/string/special/url/email/float/int 不填直接返回
     * @return null|int|string 失败返回null
     */
    public function getParam($key, $default = "", $filter = "")
    {
        //key没传递，返回false
        if ($key === "") {
            return null;
        }

        $result = $this->post($key, "", $filter);
        if ($result === "" || $result === null) {
            $result = $this->get($key, "", $filter);
        }

        if ($result === "" || $result === null) {
            return $default;
        }

        return $result;
    }

    /**
     * 获取请求参数，post优先级大于get
     * @param string $name 如果不传，那么默认获取全集
     * @param mixed $default 默认值
     * @param string $filter 数据过滤 可选 html/string/special/url/email/float/int 不填直接返回
     * @return array|mixed|null
     */
    public function request($name = "", $default = null, $filter = "")
    {
        if ($name === '') {
            return array_merge($this->_get, $this->_post);
        }

        $name = strtoupper($name);

        if (isset($this->_post_map[$name])) {
            return $this->filter($this->_post_map[$name], $filter);
        }

        if (isset($this->_get_map[$name])) {
            return $this->filter($this->_get_map[$name], $filter);
        }

        if ($default !== null) {
            return $default;
        }

        return null;
    }

    /**
     * 字符串过滤
     * 需要依赖fend-plugin-filter组件，html需要另外下载html purifier composer
     * html 去掉无用节点，去掉脚本、css、预防xss，需要依赖
     * special 过滤不可见字符
     * string 单引号双引号，unicode &
     * url 非网址合法字符过滤
     * email 非email合法字符过滤
     * float 浮点非法字符过滤
     * int 非法字符过滤
     * 不填 原样返回
     * @param string $data 要过滤的字符串
     * @param string $type 可选 html/string/special/url/email/float/int 不填直接返回
     * @return string
     */
    public function filter($data, $type = "html")
    {
        $type = strtolower($type . "");

        switch ($type) {
            case "html":
                return Filter::purifierHtml($data);
                break;
            case "special":
                return Filter::filterSpecialChars($data);
                break;
            case "string":
                return Filter::filterString($data);
                break;
            case "url":
                return Filter::filterUrl($data);
                break;
            case "email":
                return Filter::filterEmail($data);
                break;
            case "float":
                return Filter::filterFloat($data);
                break;
            case "int":
                return Filter::filterInt($data);
                break;
            default:
                return $data;
        }
    }


    /**
     * 用户可以更改get传入参数内容
     * 用于Controller内Init整理传入参数使用
     * @param $get
     * @param string $name
     */
    public function setQueryString($get, $name = '')
    {
        if ($name === '') {
            $this->_get = $get;
            $this->_get_map = array_change_key_case($this->_get, CASE_UPPER);
        } else {
            $this->_get[$name] = $get;
            $this->_get_map[strtoupper($name)] = $get;
        }
    }

    /**
     * 用户可以更改POST参数内容
     * 用于Controller内Init整理传入参数使用
     * @param $post
     * @param string $name
     */
    public function setPost($post, $name = '')
    {
        if ($name === '') {
            $this->_post = $post;
            $this->_post_map = array_change_key_case($this->_post, CASE_UPPER);
        } else {
            $this->_post[$name] = $post;
            $this->_post_map[strtoupper($name)] = $post;
        }
    }

    /**
     * 获取Server信息
     * @param string $name
     * @return array|mixed|string
     */
    public function server($name = "")
    {
        if ($name === '') {
            return $this->_server;
        }

        $name = strtoupper($name);

        if (!isset($this->_server[$name])) {
            return '';
        }

        return $this->_server[$name];
    }

    /**
     * 获取cookie
     * @param string $name
     * @return array|mixed|string
     */
    public function cookie($name = "")
    {
        if ($name === '') {
            return $this->_cookie;
        }

        $name = strtoupper($name);

        if (!isset($this->_cookie_map[$name])) {
            return '';
        }

        return $this->_cookie_map[$name];
    }

    /**
     * 获取提交file
     * @param string $name
     * @return array|mixed|string
     */
    public function file($name = "")
    {
        if ($name === '') {
            return $this->_file;
        }

        $name = strtoupper($name);

        if (!isset($this->_file_map[$name])) {
            return '';
        }

        return $this->_file_map[$name];
    }

    /**
     * 获取body内容
     * @return false|string|void
     * @throws \Exception
     */
    public function getRaw()
    {
        if ($this->_type === "fpm") {
            return file_get_contents("php://input");

        } elseif ($this->_type === "swoole_http") {
            /**
             * @var \Swoole\Http\Request
             */
            $request = Di::factory()->get("http_request");
            if (!$request) {
                throw new \Exception("swoole request 获取失败", 11);
            }
            return $request->rawContent();

        } else {
            throw new \Exception("未知request类型", 12);
        }
    }

    /**
     * 获取header
     * @param string $name
     * @return array|mixed|string
     */
    public function header($name = "")
    {
        if ($name === '') {
            return $this->_header;
        }

        $name = strtoupper($name);

        if (!isset($this->_header[$name])) {
            return '';
        }

        return $this->_header[$name];
    }

    /**
     * 设置路由后目标请求调用类及函数名称，用户可以在Controller Init阶段获取请求目标Controller信息
     * 注意即使更改此函数内容路由仍会调用之前预定目标
     * @param string $controllerName controller带namespace完整路径
     * @param string $action 目标调用action
     * @throws FendException
     */
    public function setController($controllerName, $action)
    {
        if (!class_exists($controllerName) || !method_exists($controllerName, $action)) {
            throw new FendException("controller set class $controllerName::$action not exist", -7001);
        }
        $this->_controller_name = $controllerName;
        $this->_controller_action = $action;
    }

    /**
     * 获取路由后调用的controller及action
     * @return array
     */
    public function getController()
    {
        return [
            "controller" => $this->_controller_name,
            "action" => $this->_controller_action,
        ];
    }

    /**
     * 获取客户端请求类型
     * @return string POST\GET
     */
    public function getMethod()
    {
        return strtoupper($this->server("REQUEST_METHOD"));
    }

    /**
     * 通过Header判断是否为Ajax请求
     * 不排除特殊情况，建议自行测试一下
     * @return bool true 为ajax发送
     */
    public function isAjax()
    {
        return $this->header("X-Requested-With") === "XMLHttpRequest";
    }

    /**
     * 检测微信访问
     * @return bool
     * @date 2019/11/7
     */
    public function isWechat()
    {
        $userAgent = $this->header('User-Agent');
        return stripos($userAgent, 'MicroMessenger') !== false;
    }

    /**
     * 检测Android访问
     * @return bool
     * @date 2019/11/7
     */
    public function isAndroid()
    {
        $userAgent = $this->header('User-Agent');
        return stripos($userAgent, 'Android') !== false;
    }

    /**
     * 检测是否IOS访问
     * @return bool
     * @date 2019/11/7
     */
    public function isIOS()
    {
        $userAgent = $this->header('User-Agent');
        return stripos($userAgent, 'iOS') !== false || stripos($userAgent, 'iPhone') !== false;
    }

    /**
     * 检测小程序访问
     * @return bool
     * @date 2019/11/7
     * @see https://developers.weixin.qq.com/miniprogram/dev/framework/ability/network.html
     */
    public function isWechatMiniProgram()
    {
        $referer = $this->header('Referer');
        return stripos($referer, 'https://servicewechat.com') !== false;
    }

    /**
     * 获取微信小程序版本
     * @return mixed|null 0表示开发版、体验版和审核版，devtools表示开发者工具，其余为正式版
     * @date 2019/11/7
     */
    public function getWechatMiniProgramVersion()
    {
        $referer = $this->header('Referer');
        $pattern = '/^https:\/\/servicewechat.com\/(\w+)\/(.+)\/page-frame.html/';
        preg_match($pattern, $referer, $matches);
        if (isset($matches[2])) {
            return $matches[2];
        }
        return null;
    }

    /**
     * 获取微信小程序APPID
     * @return mixed|null
     * @date 2019/11/7
     */
    public function getWechatMiniProgramAppId()
    {
        $referer = $this->header('Referer');
        $pattern = '/https:\/\/servicewechat.com\/(\w+)\/(.+)\/page-frame.html/';
        preg_match($pattern, $referer, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }
        return null;
    }
}