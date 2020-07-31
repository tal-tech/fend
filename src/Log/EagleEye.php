<?php
namespace Fend\Log;

use Fend\Config;
use Fend\Di;

/**
 * Class \Fend\LogAgent\EagleEye
 * 分布式链路 跟踪类
 */
class EagleEye
{

    //set at first
    public static $_server_ip = "";
    public static $_version = "fend-1.2";
    public static $_department = "tal_wx";

    //running parameter
    //must reset every request
    public static $_start_timestamp = 0;
    public static $_trace_id = "";
    public static $_rpc_id = "1";
    public static $_rpc_id_seq = 1;
    public static $_pid = "";

    //depend client var
    public static $_context = array();
    public static $_extra_context = array();

    //init 标志，如果不是1，所有埋点每次都会产生一个trace_id
    public static $_init = 0;

    //disable 开关
    public static $_disable = true;

    //是否在灰度压测状态
    public static $_gray = false;

    public static $avalibleKey = array(
        "x_name" => "string",
        "x_trace_id" => "string",
        "x_rpc_id" => "string",
        "x_department" => "string",
        "x_version" => "string",
        "x_timestamp" => "int",
        "x_duration" => "float",
        "x_module" => "string",
        "x_source" => "string",
        "x_uid" => "string",
        "x_pid" => "string",
        "x_server_ip" => "string",
        "x_client_ip" => "string",
        "x_user_agent" => "string",
        "x_host" => "string",
        "x_db" => "string",
        "x_code" => "string",
        "x_msg" => "string",
        "x_backtrace" => "string",
        "x_action" => "string",
        "x_param" => "string",
        "x_file" => "string",
        "x_line" => "string",
        "x_response" => "string",
        "x_response_length" => "int",
        "x_dns_duration" => "float",
        "x_instance_name" => "string",
        //"x_extra" => "string", process on bottom

    );

    /**
     * 设置当前请求变量使用，会在被请求完毕后产生一条日志,此用于记录每次被请求情况的附加和可选参数的设置
     * @param string $key 可选项目 uid code client_ip action source user_agent param 非此选项则会记录在日志extra字段内
     * @param string $val 值内容
     */
    public static function setRequestLogInfo($key, $val)
    {
        if (trim($key) != "") {
            if (in_array($key, array("uid", "code", "client_ip", "action", "source", "user_agent", "param", "response",
                                     "response_length", "msg", "backtrace"), true)) {
                self::$_context[$key] = $val . "";
            } else {
                self::$_extra_context[$key] = $val;
            }
        }
    }
    
    /**
     * 批量设置请求变量，功能同上
     * @param $data
     */
    public static function setMultiRequestLogInfo($data)
    {
        foreach ($data as $key => $item) {
            self::setRequestLogInfo($key, $item);
        }
    }

    public static function getRequestLogInfo($key = "")
    {
        if (trim($key) != "") {
            if (in_array($key, array("uid", "code", "client_ip", "action", "source", "user_agent", "param", "response",
                                     "response_length", "msg", "backtrace"), true)) {
                if (isset(self::$_context[$key])) {
                    return self::$_context[$key];
                }
                return "";
            } else {
                return self::$_extra_context[$key];
            }
        }
        return self::$_extra_context;
    }

    public static function resetRequestLogInfo()
    {
        self::$_context = array();
        self::$_extra_context = array();
    }

    /**
     * 特殊情况下禁用eagle eye日志
     * @param bool $disable 设置为true禁用，false启用
     */
    public static function disable($disable = true)
    {
        self::$_disable = $disable;
    }

    /**
     * 检测是否可用
     * @return bool true开启状态，false禁用状态
     */
    public static function isEnable()
    {
        return !self::$_disable;
    }

    /**
     * 每次框架被请求开始调用，用来初始化
     * @param string $trace_id 如果其他接口传递过来traceid设置此值
     * @param string $rpc_id 如果其他接口传递过来rpcid 设置此值
     */
    public static function requestStart($trace_id = "", $rpc_id = "")
    {
        //切换为trace_id模式
        self::$_init = 1;

        //get local ip
        self::getServerIp();

        //get my pid
        self::$_pid = getmypid();

        //恢复灰度标志
        self::$_gray = false;

        //header 如果有灰度压测标志
        $request = Di::factory()->getRequest();
        if(strtolower($request->header("Xes_Request_Type")) === "performance-testing"
         || strtolower($request->header("Xes-Request-Type")) === "performance-testing"
        ) {
            //开启灰度标志
            self::$_gray = true;
        }

        //set trace id by parameter
        if ($trace_id == "") {
            //general trace id
            self::generalTraceId();
        } else {
            if(substr($trace_id,0,4) === "pts_") {
                //pts_开头开启灰度标志
                self::$_gray = true;
            }
            self::$_trace_id = $trace_id;
        }

        //reset rpc id and seq
        if ($rpc_id == "") {
            self::$_rpc_id = "1";
        } else {
            self::$_rpc_id = $rpc_id;
        }
        self::$_rpc_id_seq = 1;


        //record start timestamp
        self::$_start_timestamp = microtime(true);

        self::resetRequestLogInfo();
    }


    /**
     * 请求结束后调用
     * 用于记录请求信息、结果
     */
    public static function requestFinished()
    {
        //set at first
        $log = array(
            "x_action" => self::filterUrl(self::getRequestLogInfo("action")),
            "x_name" => "request.info",
            "x_version" => self::$_version,
            "x_trace_id" => self::$_trace_id,
            "x_rpc_id" => self::getReciveRpcId().".1",
            "x_department" => self::$_department,
            "x_server_ip" => self::getServerIp(),
            "x_timestamp" => (int)self::$_start_timestamp,
            "x_duration" => round(microtime(true) - self::$_start_timestamp, 4),
            "x_pid" => self::$_pid . "",
            "x_module" => "php_request_end",
            "x_extra" => self::$_extra_context
        );

        //option value added
        foreach (self::$_context as $key => $val) {
            $log["x_" . $key] = $val;
        }
        $log = self::formatLog($log);
        self::recordlog($log);
    }

    /**
     * EagleEye其他类型日志记录函数，自动填写公用日志内容
     * 如mysql、redis、memcache、websocket、http的 连接、查询、关闭、错误
     * @param array $param 日志内容，非规定字段会加到extra
     * @param string $rpc_id 如果设置，那么此次日志使用设置值作为rpcid进行记录，用于请求其他资源时先生成rpcid
     */
    public static function baseLog($param, $rpc_id = "")
    {
        if (self::$_disable) {
            return;
        }

        //set at first
        $log = array(
            "x_version"     => self::$_version,
            "x_trace_id"    => self::$_trace_id,
            "x_department"  => self::$_department,
            "x_server_ip"   => self::getServerIp(),
            "x_timestamp"   => time(),
            "x_pid"         => getmypid(),
            "x_uid"         => self::getRequestLogInfo("uid"),
            "x_client_ip"   => self::getRequestLogInfo("client_ip"),
        );

        //rpc id value decide
        if ($rpc_id == "") {
            $log["x_rpc_id"] = self::getNextRpcId();
        } else {
            $log["x_rpc_id"] = $rpc_id;
        }

        //filter the path root
        if(isset($param["x_file"])) {
            $param["x_file"] = substr($param["x_file"], strlen(SYS_ROOTDIR) - 1);
        }

        //filter the response length
        if (isset($param["x_response"])) {
            $traceResponseMaxLen = Config::get("Fend.log.traceResponseMaxLen", -1);

            if($traceResponseMaxLen > 0) {
                //large more cut down
                $param["x_response"] = (strlen($param["x_response"]) > $traceResponseMaxLen) ? substr($param["x_response"],0, $traceResponseMaxLen). "..." : $param["x_response"];
            }elseif($traceResponseMaxLen === 0) {
                //== 0 mean the clean
                $param["x_response"] = "";
            }
        }

        //format input log
        $log = self::formatLog(array_merge($log, $param));

        //record log
        self::recordlog($log);
    }

    /**
     * 统一日志格式化函数
     * @param $log
     * @return array
     */
    public static function formatLog($log)
    {
        $format_log = array();
        $unknow_field = array();

        //foreach and filter the field
        foreach ($log as $key => $val) {

            if ($key == "x_extra") {
                continue;
            }

            if (isset(self::$avalibleKey[$key])) {
                //convert the value type
                if (self::$avalibleKey[$key] === "string") {
                    if(is_float($val)) {
                        $val = round($val,4)."";
                    }else if (is_numeric($val)) {
                        $val = $val . "";
                    } else if (is_array($val)) {
                        $val = json_encode($val);
                    }
                } elseif (self::$avalibleKey[$key] === "int") {
                    $val = intval($val);
                } elseif (self::$avalibleKey[$key] === "float") {
                    $val = round(floatval($val), 4)."";
                }
                $format_log[$key] = $val;
            } else {
                $unknow_field[$key] = $val;
            }
        }

        //append unknow field

        if (isset($log["x_extra"]) && is_array($log["x_extra"])) {
            $log["x_extra"]["unknow"] = $unknow_field;
            $format_log["x_extra"] = json_encode($log["x_extra"]);
        } else if (isset($log["x_extra"]) && is_string($log["x_extra"])) {
            $log["x_extra"] = json_decode($log["x_extra"],true);
            $log["x_extra"]["unknow"] = $unknow_field;
            $format_log["x_extra"] = json_encode($log["x_extra"]);
        } else {
            $log["x_extra"]["unknow"] = $unknow_field;
            $format_log["x_extra"] = json_encode($log["x_extra"]);
        }

        return $format_log;
    }

    public static function getServerIp()
    {
        if (self::$_server_ip == "") {
            self::$_server_ip = gethostname();
        }
        return self::$_server_ip;
    }

    /**
     * 获取当前traceid
     * @return string
     */
    public static function getTraceId()
    {
        //如果没有初始化，那么每次请求都会用一个trace_id
        if (self::$_trace_id == "" || self::$_init == 0) {
            self::generalTraceId();
        }
        return self::$_trace_id;
    }

    /**
     * 刷新重新生成当前TraceID
     * @return string
     */
    public static function generalTraceId()
    {

        //get local ip
        if (self::$_server_ip == "") {
            self::$_server_ip = gethostname();
        }

        self::$_trace_id = self::$_server_ip . "_" . getmypid() . "_" . (microtime(true) - 1483200000) . "_" . mt_rand(0, 255);
        return self::$_trace_id;
    }

    /**
     * 获取当前RPCID前段，不含自增值
     * @return string
     */
    public static function getReciveRpcId()
    {
        return self::$_rpc_id;
    }

    /**
     * 获取当前rpcid 包括当前计数
     * @return string
     */
    public static function getCurrentRpcId()
    {
        return self::$_rpc_id . "." . self::$_rpc_id_seq;
    }

    /**
     * 获取下一个RPC ID,发送给被调用方
     * @return string
     */
    public static function getNextRpcId()
    {
        if (self::$_init == 0) {
            self::$_rpc_id     = 1;
            self::$_rpc_id_seq = 1;
            return self::$_rpc_id . "." . self::$_rpc_id_seq;
        }
        self::$_rpc_id_seq++;
        return self::$_rpc_id . "." . self::$_rpc_id_seq;
    }

    /**
     * 设置当前服务版本
     * @param $version
     */
    public static function setVersion($version)
    {
        self::$_version = $version;
    }

    /**
     * 获取当前服务版本
     * @return string
     */
    public static function getVersion()
    {
        return self::$_version;
    }

    /**
     * 性能记录开始，会返回耗时时间
     * 找个地方记录好这个返回
     * @return mixed
     */
    public static function startDuration()
    {
        return microtime(true);
    }

    /**
     * 性能记录结束，传入之前开始返回值，会返回耗时时间
     * @param $startPoint
     * @return mixed
     */
    public static function endDuration($startPoint)
    {
        return microtime(true) - $startPoint;
    }

    /**
     * 获取灰度状态
     * @return bool
     */
    public static function getGrayStatus()
    {
        return self::$_gray;
    }

    /**
     * 开启灰度模式
     */
    public static function setGrayStatus($enable = true)
    {
        self::$_gray = $enable;
    }

    /**
     * 写日志
     * @param mixed $log
     */
    private static function recordlog($log)
    {
        if (self::$_disable) {
            return;
        }
        LogAgent::log($log);
    }

    /**
     * 用于网址参数过滤
     * @param $url
     * @return string
     */
    public static function filterUrl(string $url)
    {
        if ($url && strlen($url) > 0 && ($pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $pos);
        }
        return $url;
    }
}
