<?php

namespace Fend\Funcs;

use Fend\Di;
use Fend\Exception\SystemException;
use Fend\Log\EagleEye;

/**
 * 网络相关
 *
 **/
class FendHttp
{

    /**
     * get 通用处理方法
     * @param $name
     *
     * @return null|string
     */
    public static function doGet($name)
    {
        $request = \Fend\Di::factory()->getRequest();
        $param = $request->get($name);
        if (!is_null($param)) {
            return is_array($param) ? $param : trim($param);
        } else {
            return null;
        }
    }

    /**
     * Post 通用处理方法
     * @param $name
     *
     * @return null|string
     */
    public static function doPost($name)
    {
        $request = \Fend\Di::factory()->getRequest();
        $param = $request->post($name);
        if (!is_null($param)) {
            return is_array($param) ? $param : trim($param);
        } else {
            return null;
        }

    }

    /**
     * quest 通用处理方法
     * @param $name
     *
     * @return null|string
     */
    public static function doRequest($name)
    {
        $request = \Fend\Di::factory()->getRequest();
        $param = $request->post($name);
        if ($param === '') {
            $param = $request->get($name);
        }

        if (!is_array($param)) {
            $param = trim($param);
        }

        return $param;

    }

    //写cookie信息
    public static function setRawCookie($name, $value, $life, $path = '/', $domain = '')
    {
        $response = \Fend\Di::factory()->getResponse();

        if (!empty($domain)) {
            $domain = '.' . $domain;
        }

        switch ($life) {
            case 0:
            case '':
            {
                $expire = time();
                break;
            }
            case -1:
            {
                $expire = 0;
                break;
            }
            default:
            {
                $expire = time() + $life;
                break;
            }
        }
        return $response->cookie($name, $value, $expire, $path, $domain);
    }

    /**
     * 获取内容的href标签
     *
     * @param string $content
     * @return array
     */
    public static function getHref($content)
    {
        $pat = '/<a(.*?)href="(.*?)"(.*?)>(.*?)<\/a>/i';
        preg_match_all($pat, $content, $hrefAry);
        return $hrefAry;
    }

    /**
     * 获取url含rar内容的href标签
     *
     * @param string $content
     * @return array
     */
    public static function getHrefRar($content)
    {
        $pat = '/<a(.*?)href="(.*?).rar"(.*?)>(.*?)<\/a>/i';
        preg_match_all($pat, $content, $hrefAry);
        return $hrefAry;
    }

    /**
     * 获取url含src内容的href标签
     *
     * @param string $content
     * @return array
     */
    public static function getHrefImg($content)
    {
        $pat = "/<img(.+?)src='(.+?)'/i";
        preg_match_all($pat, $content, $hrefAry);
        if (empty($hrefAry[0])) {
            $pat = "/<img(.+?)src=\"(.+?)\"/i";
            preg_match_all($pat, $content, $hrefAry);
        }
        return $hrefAry;
    }

    //获取客户端IP

    /**
     * 获取请求客户端IP
     * @return string
     */
    public static function getIp()
    {
        $ip = "0.0.0.0";

        $request = Di::factory()->getRequest();
        if (empty($request)) {
            return "127.0.0.1";
        }

        if (!empty($request->header('X-FORWARDED-FOR'))) {
            $ip = $request->header('X-FORWARDED-FOR');
            //ip会返回一组数据，最左边是客户端ip，其他都为历经网关IP
            $ip = explode(',', $ip);
            $ip = count($ip) > 0 ? trim($ip[0]) : "";
        } else if (!empty($request->header('X_FORWARDED_FOR'))) {
            $ip = $request->header('X_FORWARDED_FOR');
            //ip会返回一组数据，最左边是客户端ip，其他都为历经网关IP
            $ip = explode(',', $ip);
            $ip = count($ip) > 0 ? trim($ip[0]) : "";
        } else if (!empty($request->header('X-REAL-IP'))) {
            $ip = $request->header('X-REAL-IP');
        } else if (!empty($request->header('REMOTE_ADDR'))) {
            $ip = $request->header('REMOTE_ADDR');
        }

        return $ip;
    }

    /**
     * 新版本http请求封装，后续建议使用这个
     * 较之前改进：
     *   get请求会将$data数据放到url内
     *   header为kv方式传递|也支持string[]方式
     *   data支持kv数组、也支持http_build_query后字符串直接传递
     *   请求错误会抛出异常
     *   method支持大小写
     * @param string $url 请求网址
     * @param array|string $data 请求数据，如果是get会自动在网址后追加参数
     * @param string $method 数据报文 GET|POST|PUT|PATCH|DELETE
     * @param int $timeout 超时时间、单位:毫秒 连接时间默认使用同一个时间，如果有特殊需要可以设置extra参数connect_timeout
     * @param array $header kv方式的header设置
     * @param array $extra 特殊功能设置
     *      数组 $extra["option"][CURLOPT_CONNECTTIMEOUT_MS]=val 对应curl_setopt
     *      $extra["option"][connect_timeout]=3000 设置连接时间毫秒
     *      $extra["option"][retry]=3  重试次数
     * @return mixed
     * @throws SystemException
     */
    public static function httpRequest($url, $data = [], $method = 'get', $timeout = 30000, $header = array(), $extra = array())
    {
        //trace
        $rpc_id = EagleEye::getNextRpcId();

        //make sure the data array to string
        $data = empty($data) ? "" : (is_array($data) ? http_build_query($data) : $data);

        //method must be lower
        $method = strtolower($method);

        //append header for eagle eye trace id and rpc id
        $header["traceid"] = EagleEye::getTraceId();
        $header["rpcid"] = $rpc_id;

        //gray header
        if (EagleEye::getGrayStatus()) {
            $header["Xes-Request-Type"] = "performance-testing";
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout); //default connection timeout same timeout
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE); //return request header

        //extra have connect_timeout var.that will overwrite $timeout value
        if (isset($extra["connect_timeout"]) && $extra["connect_timeout"] > 0) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $extra["connect_timeout"]);
        }

        //header
        if (!empty($header)) {
            $headers = [];
            foreach ($header as $headerKey => $headerVal) {
                if (is_numeric($headerKey)) {
                    $headers[] = $headerVal;
                } else {
                    $headers[] = $headerKey . ": " . $headerVal;
                }
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        switch ($method) {
            case 'post':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'get':
                curl_setopt($ch, CURLOPT_POST, false);

                //add data to url
                if ($data != "") {
                    $url = $url . (strpos($url, "?") === false ? '?' : '&') . $data;
                }

                curl_setopt($ch, CURLOPT_URL, $url);
                break;
            case 'put':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'patch':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'delete':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            default:
                throw new SystemException("http request method $method unknow", -3222);
        }

        //设置附加header
        if (isset($extra["option"])) {
            foreach ($extra["option"] as $key => $val) {
                curl_setopt($ch, $key, $val);
            }
        }

        //default retry not work
        $retry = (isset($extra["retry"]) && $extra["retry"] > 0) ? $extra["retry"] : 1;

        //retry count
        $retryCount = 0;

        $response = false;

        while ($retry > 0) {

            //decr retry
            $retry--;
            $retryCount++;
            $start_time = microtime(true);

            //fetch result
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);

            //trace
            $eagleeye_param = array(
                "x_name" => "http." . $method,
                "x_module" => "php_http_request",
                "x_duration" => round(microtime(true) - $start_time, 4),
                "x_action" => EagleEye::filterUrl($url),
                "x_param" => $data,
                "x_file" => __FILE__,
                "x_line" => __LINE__,
                "x_dns_duration" => round(sprintf("%.f", $info["namelookup_time"]), 4),
                "x_response_length" => strlen($response),
                "x_code" => $info["http_code"],
                "curl_info" => [
                    "url" => $info["url"],
                    "primary_ip" => $info["primary_ip"],
                    "content_type" => $info["content_type"],
                    "http_code" => $info["http_code"],
                    "filetime" => $info["filetime"],
                    "redirect_count" => $info["redirect_count"],
                    "total_time" => round(sprintf("%.f", $info["total_time"]), 4),
                    "namelookup_time" => round(sprintf("%.f", $info["namelookup_time"]), 4),
                    "connect_time" => round(sprintf("%.f", $info["connect_time"]), 4),
                    "pretransfer_time" => round(sprintf("%.f", $info["pretransfer_time"]), 4),
                    "speed_download" => $info["speed_download"],
                    "speed_upload" => $info["speed_upload"],
                ],
            );

            //http error
            if ($response === false) {
                $eagleeye_param["x_name"] = $eagleeye_param["x_name"] . ".error";
                $eagleeye_param["x_code"] = curl_errno($ch);
                $eagleeye_param["x_msg"] = curl_error($ch);
                $eagleeye_param["x_backtrace"] = self::getTraceString();
                $eagleeye_param["x_response"] = $response;

                //record eagle eye
                EagleEye::baseLog($eagleeye_param, $rpc_id);

                //end
                if ($retry <= 0) {
                    curl_close($ch);
                    throw new SystemException("http request url: $url fail reason:{$eagleeye_param["x_msg"]}({$eagleeye_param["x_code"]})  method: $method data:$data retry:$retryCount", -$eagleeye_param["x_code"]);
                }

                //retry
                continue;
            }

            //success
            break;
        }


        //success
        $eagleeye_param["x_response"] = $response;

        //如果设置返回header
        $serverHeader = [];
        if (isset($extra["option"][CURLOPT_HEADER]) && $extra["option"][CURLOPT_HEADER]) {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            // 根据头大小去获取头信息内容
            $serverHeader = explode("\r\n", substr($response, 0, $headerSize));
            $responseHeaders = [];
            foreach ($serverHeader as $item) {
                $item = explode(": ", $item);
                if (count($item) === 2) {
                    $responseHeaders[$item[0]] = $item[1];
                }
            }
            $serverHeader = $responseHeaders;
            $response = substr($response, $headerSize);
        }

        //try to decode json
        $result = @json_decode($response, true);
        if (empty($result)) {
            $result = $response;
        }

        //record eagle eye
        EagleEye::baseLog($eagleeye_param, $rpc_id);
        curl_close($ch);

        return ["response" => $result, "info" => $info, "header" => $serverHeader, "retry" => $retryCount];
    }

    /**
     * 发送数据
     * @param string $url 数据报文
     * @param string|array $data 数据报文
     * @param string $method 数据报文
     * @param int $time timeout 单位：毫秒
     * @param array|string[] $header header头，支持string[]及kv方式传递
     * @param array $cert 证书
     * @param array curl 附加option
     * @return mixed
     */
    public static function getUrl($url, $data = '', $method = 'get', $time = 30000, $header = array(), $cert = array(), $option = array())
    {
        $rpc_id = EagleEye::getNextRpcId();

        $data = empty($data) ? "" : (is_array($data) ? http_build_query($data) : $data);

        //append header for eagle eye trace id and rpc id
        $header[] = "traceid: " . EagleEye::getTraceId();
        $header[] = "rpcid: " . $rpc_id;

        //gray header
        if (EagleEye::getGrayStatus()) {
            $header["Xes-Request-Type"] = "performance-testing";
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($ch, CURLOPT_NOSIGNAL, true); //支持毫秒级别超时设置
        if (!empty($header)) {
            $headers = [];
            foreach ($header as $headerKey => $headerVal) {
                if (is_numeric($headerKey)) {
                    $headers[] = $headerVal;
                } else {
                    $headers[] = $headerKey . ": " . $headerVal;
                }
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        //需要pem证书双向认证
        if (!empty($cert)) {
            //证书的类型
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, $cert['type']);
            //PEM文件地址
            curl_setopt($ch, CURLOPT_SSLCERT, $cert['cert']);
            //私钥的加密类型
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, $cert['type']);
            //私钥地址
            curl_setopt($ch, CURLOPT_SSLKEY, $cert['key']);
        }
        /*
          curl_setopt($ch, CURLOPT_SSLCERT, $this->config['cert']);
          curl_setopt($ch, CURLOPT_SSLCERTTYPE, $this->config['certtype']);
          curl_setopt($ch, CURLOPT_SSLKEY, $this->config['key']);

          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
         */
        if ($method === 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, NULL);
        } elseif ($method === 'get') {
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, NULL);
        } elseif ($method === 'put') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif ($method === 'patch') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif ($method === 'delete') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 3000);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $time);

        //设置附加option
        if (!empty($option)) {
            foreach ($option as $key => $val) {
                curl_setopt($ch, $key, $val);
            }
        }

        $start_time = microtime(true);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        $eagleeye_param = array(
            "x_name" => "http." . $method,
            "x_module" => "php_http_request",
            "x_duration" => round(microtime(true) - $start_time, 4),
            "x_action" => EagleEye::filterUrl($url),
            "x_param" => $data,
            "x_file" => __FILE__,
            "x_line" => __LINE__,
            "x_dns_duration" => round(sprintf("%.f", $info["namelookup_time"]), 4),
            "x_response_length" => strlen($response),
            "x_code" => $info["http_code"],
            "curl_info" => [
                "url" => $info["url"],
                "primary_ip" => $info["primary_ip"],
                "content_type" => $info["content_type"],
                "http_code" => $info["http_code"],
                "filetime" => $info["filetime"],
                "redirect_count" => $info["redirect_count"],
                "total_time" => round(sprintf("%.f", $info["total_time"]), 4),
                "namelookup_time" => round(sprintf("%.f", $info["namelookup_time"]), 4),
                "connect_time" => round(sprintf("%.f", $info["connect_time"]), 4),
                "pretransfer_time" => round(sprintf("%.f", $info["pretransfer_time"]), 4),
                "speed_download" => $info["speed_download"],
                "speed_upload" => $info["speed_upload"],
            ],
        );

        //http error
        if ($response === false) {
            $eagleeye_param["x_name"] = $eagleeye_param["x_name"] . ".error";
            $eagleeye_param["x_code"] = curl_errno($ch);
            $eagleeye_param["x_msg"] = curl_error($ch);
            $eagleeye_param["x_backtrace"] = self::getTraceString();
            $eagleeye_param["x_response"] = $response;

            //record eagle eye
            EagleEye::baseLog($eagleeye_param, $rpc_id);
            curl_close($ch);

            return false;
        } else {
            //success
            $eagleeye_param["x_response"] = $response;

            json_decode($response);
            if (json_last_error() === JSON_ERROR_NONE) {
                $return = json_decode($response, true);
            } else {
                $return = $response;
            }
            //record eagle eye
            EagleEye::baseLog($eagleeye_param, $rpc_id);
            curl_close($ch);

            return $return;
        }
    }


    /**
     * 批量请求网址
     *
     *
     * $urlList = [
     *               [
     *                   "url" => "http://www.fend.com/",
     *                   "method" => "post",
     *                   "data" => ["search"=>"q"],
     *               ],
     *               [
     *                   "url" => "http://www.fend1.com/",
     *                   "method" => "post",
     *                   "data" => ["search"=>"q"],
     *               ],
     *               [
     *                   "url" => "http://www.fend02.com/",
     *                   "method" => "post",
     *                   "data" => ["search"=>"q"],
     *               ]
     *           ];
     * @param $urlList
     * @param int $time
     * @return mixed
     */
    public static function getMulti($urlList, $time = 5000)
    {
        $handleList = [];
        $rpcidList = [];

        $mh = curl_multi_init();
        $start_time = microtime(true);

        foreach ($urlList as $index => $urlItem) {
            $url = $urlItem["url"];
            $cert = isset($urlItem["cert"]) && !empty($urlItem["cert"]) ? $urlItem["cert"] : array();
            $method = isset($urlItem["method"]) && !empty($urlItem["method"]) ? $urlItem["method"] : $urlItem["method"] = "get";
            $data = isset($urlItem["data"]) && !empty($urlItem["data"]) ? $urlItem["data"] : array();
            $data = empty($data) ? "" : (is_array($data) ? http_build_query($data) : $data);

            $rpc_id = EagleEye::getNextRpcId();
            $rpcidList[$index] = $rpc_id;

            //append header for eagle eye trace id and rpc id
            $header = isset($urlItem["header"]) && !empty($urlItem["header"]) ? $urlItem["header"] : array();
            $header[] = "traceid: " . EagleEye::getTraceId();
            $header[] = "rpcid: " . $rpc_id;

            //gray header
            if (EagleEye::getGrayStatus()) {
                $header["Xes-Request-Type"] = "performance-testing";
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

            curl_setopt($ch, CURLOPT_NOSIGNAL, true); //支持毫秒级别超时设置
            if (!empty($header)) {
                $headers = [];
                foreach ($header as $headerKey => $headerVal) {
                    if (is_numeric($headerKey)) {
                        $headers[] = $headerVal;
                    } else {
                        $headers[] = $headerKey . ": " . $headerVal;
                    }
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            //需要pem证书双向认证
            if (!empty($cert)) {
                //证书的类型
                curl_setopt($ch, CURLOPT_SSLCERTTYPE, $cert['type']);
                //PEM文件地址
                curl_setopt($ch, CURLOPT_SSLCERT, $cert['cert']);
                //私钥的加密类型
                curl_setopt($ch, CURLOPT_SSLKEYTYPE, $cert['type']);
                //私钥地址
                curl_setopt($ch, CURLOPT_SSLKEY, $cert['key']);
            }

            if ($method === 'post') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, NULL);
            } elseif ($method === 'get') {
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, NULL);
            } elseif ($method === 'put') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } elseif ($method === 'patch') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } elseif ($method === 'delete') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 3000);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $time);

            curl_multi_add_handle($mh, $ch);
            $handleList[$index] = $ch;
        }

        //exec
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status === CURLM_OK);

        //get result
        foreach ($handleList as $index => $handle) {
            $response = curl_multi_getcontent($handle);

            $info = curl_getinfo($handle);
            $eagleeye_param = array(
                "x_name" => "http." . $urlList[$index]["method"],
                "x_module" => "php_http_request",
                "x_duration" => round(microtime(true) - $start_time, 4),
                "x_action" => EagleEye::filterUrl($urlList[$index]["url"]),
                "x_param" => $urlList[$index]["data"],
                "x_file" => __FILE__,
                "x_line" => __LINE__,
                "x_dns_duration" => round(sprintf("%.f", $info["namelookup_time"]), 4),
                "x_response_length" => strlen($response),
                "x_code" => curl_getinfo($handle, CURLINFO_HTTP_CODE),
                "curl_info" => [
                    "url" => $info["url"],
                    "primary_ip" => $info["primary_ip"],
                    "content_type" => $info["content_type"],
                    "http_code" => $info["http_code"],
                    "filetime" => $info["filetime"],
                    "redirect_count" => $info["redirect_count"],
                    "total_time" => round(sprintf("%.f", $info["total_time"]), 4),
                    "namelookup_time" => round(sprintf("%.f", $info["namelookup_time"]), 4),
                    "connect_time" => round(sprintf("%.f", $info["connect_time"]), 4),
                    "pretransfer_time" => round(sprintf("%.f", $info["pretransfer_time"]), 4),
                    "speed_download" => $info["speed_download"],
                    "speed_upload" => $info["speed_upload"],
                ],
            );

            //http error
            if ($eagleeye_param["x_code"] === 0) {
                $eagleeye_param["x_name"] = $eagleeye_param["x_name"] . ".error";
                $eagleeye_param["x_code"] = curl_errno($handle);
                $eagleeye_param["x_msg"] = curl_error($handle);
                $eagleeye_param["x_backtrace"] = self::getTraceString();
                $eagleeye_param["x_response"] = $response;

                //record eagle eye
                EagleEye::baseLog($eagleeye_param, $rpcidList[$index]);
                $result[$index] = false;

            } else {
                //success
                $eagleeye_param["x_response"] = $response;

                $return = json_decode($response, true);
                if (json_last_error() != JSON_ERROR_NONE) {
                    $return = $response;
                }
                //record eagle eye
                EagleEye::baseLog($eagleeye_param, $rpcidList[$index]);
                $result[$index] = $return;
            }
            curl_multi_remove_handle($mh, $handle);
        }

        curl_multi_close($mh);
        return $result;
    }

    /**
     * http访问远程接口，返回array包括错误码及result
     * @param $url
     * @param string|array $data
     * @param string $method = 'get'
     * @param int $time = 30000ms
     * @param array|string[] $header = array()
     * @param array $cert
     * @param array curl 附加option
     * @return array
     */
    public static function getHttp($url, $data = '', $method = 'get', $time = 30000, $header = array(), $cert = array(), $option = array())
    {
        $rpc_id = EagleEye::getNextRpcId();
        $start_time = microtime(true);
        $data = empty($data) ? "" : (is_array($data) ? http_build_query($data) : $data);

        //append header for eagle eye trace id and rpc id
        $header[] = "traceid: " . EagleEye::getTraceId();
        $header[] = "rpcid: " . $rpc_id;

        //gray header
        if (EagleEye::getGrayStatus()) {
            $header["Xes-Request-Type"] = "performance-testing";
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($ch, CURLOPT_NOSIGNAL, true); //支持毫秒级别超时设置
        if (!empty($header)) {
            $headers = [];
            foreach ($header as $headerKey => $headerVal) {
                if (is_numeric($headerKey)) {
                    $headers[] = $headerVal;
                } else {
                    $headers[] = $headerKey . ": " . $headerVal;
                }
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        //需要pem证书双向认证
        if (!empty($cert)) {
            //证书的类型
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, $cert['type']);
            //PEM文件地址
            curl_setopt($ch, CURLOPT_SSLCERT, $cert['cert']);
            //私钥的加密类型
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, $cert['type']);
            //私钥地址
            curl_setopt($ch, CURLOPT_SSLKEY, $cert['key']);
        }
        if ($method === 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, NULL);
        } elseif ($method === 'get') {
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, NULL);
        } elseif ($method === 'put') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif ($method === 'patch') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif ($method === 'delete') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 3000);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $time);

        //设置附加option
        if (!empty($option)) {
            foreach ($option as $key => $val) {
                curl_setopt($ch, $key, $val);
            }
        }

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        $eagleeye_param = array(
            "x_name" => "http." . $method,
            "x_module" => "php_http_request",
            "x_duration" => round(microtime(true) - $start_time, 4),
            "x_action" => EagleEye::filterUrl($url),
            "x_param" => $data,
            "x_file" => __FILE__,
            "x_line" => __LINE__,
            "x_dns_duration" => round(sprintf("%.f", curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME)), 4),
            "x_response_length" => strlen($response),
            "x_code" => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            "curl_info" => [
                "url" => $info["url"],
                "primary_ip" => $info["primary_ip"],
                "content_type" => $info["content_type"],
                "http_code" => $info["http_code"],
                "filetime" => $info["filetime"],
                "redirect_count" => $info["redirect_count"],
                "total_time" => round(sprintf("%.f", $info["total_time"]), 4),
                "namelookup_time" => round(sprintf("%.f", $info["namelookup_time"]), 4),
                "connect_time" => round(sprintf("%.f", $info["connect_time"]), 4),
                "pretransfer_time" => round(sprintf("%.f", $info["pretransfer_time"]), 4),
                "speed_download" => $info["speed_download"],
                "speed_upload" => $info["speed_upload"],
            ],
        );

        if (curl_errno($ch) === 0) {
            $eagleeye_param["x_response"] = $response;
            EagleEye::baseLog($eagleeye_param, $rpc_id);

            json_decode($response);
            if (json_last_error() === JSON_ERROR_NONE) {
                $return = json_decode($response, true);
            } else {
                $return = $response;
            }
            curl_close($ch);
            return array(0, $return);
        } else {
            $eagleeye_param["x_name"] = $eagleeye_param["x_name"] . ".error";
            $eagleeye_param["x_code"] = curl_errno($ch);
            $eagleeye_param["x_msg"] = curl_error($ch);
            $eagleeye_param["x_backtrace"] = self::getTraceString();
            $eagleeye_param["x_response"] = $response;

            //record eagle eye
            EagleEye::baseLog($eagleeye_param, $rpc_id);
            curl_close($ch);
            return array($eagleeye_param["x_code"], $eagleeye_param["x_msg"]);
        }
    }

    /**
     * 获取本次调用堆栈层级文字描述
     * @return string
     */
    public static function getTraceString()
    {
        $result = "";
        $line = 0;
        $backtrace = debug_backtrace();
        foreach ($backtrace as $btrace) {
            if (!empty($btrace["file"]) && !empty($btrace["line"])) {
                $result .= sprintf("#%s %s(%s) %s%s%s(%s)\n", $line, $btrace["file"], $btrace["line"], $btrace["class"] ?? '', $btrace["type"] ?? '', $btrace["function"] ?? '', http_build_query($btrace));
                $line++;
            }
        }
        return $result;
    }

    /**
     * 跳转-2:关闭当前窗口
     * @param $str
     */
    public static function getClose($str)
    {
        echo "<SCRIPT LANGUAGE='JavaScript'>alert('" . $str . "');window.close();</SCRIPT>";
        return;
    }

    /**
     * @param null $url
     *  302重定向 默认定向到来访页面
     */
    public static function doBreak($url = null)
    {
        $response = \Fend\Di::factory()->get('http_response');
        !$url && $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
        if (!empty($response)) {
            $response->header("location", $url);
            $response->status('302');
        } else {
            header("location:{$url}");
        }
        //中断后续代码执行
        throw new \Fend\Exception\ExitException("");
    }
}
