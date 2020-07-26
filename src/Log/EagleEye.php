<?php

namespace Fend\Log;

use Fend\Core\RequestContext;
use Fend\Config;
use Fend\Di;

/**
 * Class \Fend\LogAgent\EagleEye
 * 分布式链路 跟踪类
 */
class EagleEye
{
    //set at first
    protected const FIELD_SERVER_IP = "Trace.ServerIP";
    protected const FIELD_VERSION = "Trace.Version";
    protected const FIELD_DEPARTMENT = "Trace.Department";

    //running parameter
    //must reset every request
    protected const FIELD_START_TIMESTAMP = "Trace.StartTimestamp";
    protected const FIELD_TRACE_ID = "Trace.TraceID";
    public const FIELD_RPC_ID = "Trace.RPC_ID";
    public const FIELD_RPC_ID_SEQ = "Trace.RPC_ID_SEQ";
    protected const FIELD_PID = "Trace.Pid";

    //depend client var
    protected const FIELD_CONTEXT = "Trace.Context";
    protected const FIELD_EXTRA_CONTEXT = "Trace.ExtraContext";

    //init 初始化标志, 0为未初始化 未初始化之前traceid每次都会生成 RPCID会一直持续1.1(解决脚本traceid问题)
    protected const FIELD_INIT = "Trace.Init";

    //是否禁用trace: true为禁用
    protected static $_disable = false;

    //是否在灰度压测状态
    protected const FIELD_GRAY = "Trace.Gray";

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
                RequestContext::set(self::FIELD_CONTEXT . "." . $key, $val . "");
            } else {
                RequestContext::set(self::FIELD_EXTRA_CONTEXT . "." . $key, $val . "");
            }
        }
    }

    /**
     * 批量设置请求变量，功能同上
     * @param $data
     */
    public static function setMultiRequestLogInfo($data)
    {
        $context = [];

        foreach ($data as $key => $val) {
            if (trim($key) != "") {
                if (in_array($key, array("uid", "code", "client_ip", "action", "source", "user_agent", "param", "response",
                    "response_length", "msg", "backtrace"), true)) {
                    $context[self::FIELD_CONTEXT . "." . $key] = $val . "";
                } else {
                    $context[self::FIELD_EXTRA_CONTEXT . "." . $key] = $val . "";
                }
            }
        }

        if ($context) {
            RequestContext::setMulti($context);
        }

    }

    public static function getRequestLogInfo($key = "")
    {
        if (trim($key) != "") {
            if (in_array($key, array("uid", "code", "client_ip", "action", "source", "user_agent", "param", "response",
                "response_length", "msg", "backtrace"), true)) {
                return RequestContext::get(self::FIELD_CONTEXT . "." . $key, "");
            } else {
                return RequestContext::get(self::FIELD_EXTRA_CONTEXT . "." . $key, "");
            }
        }
        return RequestContext::get(self::FIELD_EXTRA_CONTEXT, []);
    }

    public static function resetRequestLogInfo()
    {
        RequestContext::setMulti([
            self::FIELD_CONTEXT=> [],
            self::FIELD_EXTRA_CONTEXT => [],
        ]);
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

        //get local ip
        self::getServerIp();

        $data = [
            self::FIELD_INIT => 1,
            self::FIELD_PID => getmypid(),
            self::FIELD_GRAY => false,
            self::FIELD_TRACE_ID => $trace_id == "" ? self::generalTraceId() : $trace_id,
            self::FIELD_RPC_ID => $rpc_id == "" ? "1" : $rpc_id,
            self::FIELD_RPC_ID_SEQ => 1,
            self::FIELD_START_TIMESTAMP => microtime(true),
        ];

        $request = Di::factory()->getRequest();
        if (!empty($request) && ($trace_id !== "" && substr($trace_id, 0, 4) === "pts_"
                || strtolower($request->header("Xes_Request_Type")) === "performance-testing"
                || strtolower($request->header("Xes-Request-Type")) === "performance-testing")
        ) {
            //pts_开头开启灰度标志
            $data[self::FIELD_GRAY] = true;
        }

        RequestContext::setMulti($data);
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
            "x_name" => "request.info",
            "x_version" => RequestContext::get(self::FIELD_VERSION, "fend-1.2"),
            "x_trace_id" => RequestContext::get(self::FIELD_TRACE_ID),
            "x_rpc_id" => self::getReciveRpcId() . ".1",
            "x_department" => RequestContext::get(self::FIELD_DEPARTMENT, "tal_wx"),
            "x_server_ip" => self::getServerIp(),
            "x_timestamp" => (int)RequestContext::get(self::FIELD_START_TIMESTAMP),
            "x_duration" => round(microtime(true) - RequestContext::get(self::FIELD_START_TIMESTAMP), 4),
            "x_pid" => RequestContext::get(self::FIELD_PID),
            "x_module" => "php_request_end",
            "x_extra" => RequestContext::get(self::FIELD_EXTRA_CONTEXT, [])
        );

        //option value added
        foreach (RequestContext::get(self::FIELD_CONTEXT, []) as $key => $val) {
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
            "x_version" => RequestContext::get(self::FIELD_VERSION, "fend-1.2"),
            "x_trace_id" => RequestContext::get(self::FIELD_TRACE_ID),
            "x_department" => RequestContext::get(self::FIELD_DEPARTMENT, "tal_wx"),
            "x_server_ip" => self::getServerIp(),
            "x_timestamp" => time(),
            "x_pid" => getmypid(),
            "x_uid" => self::getRequestLogInfo("uid"),
            "x_client_ip" => self::getRequestLogInfo("client_ip"),
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
                    if (is_float($val)) {
                        $val = round($val, 4) . "";
                    } else if (is_numeric($val)) {
                        $val = $val . "";
                    } else if (is_array($val)) {
                        $val = json_encode($val);
                    }
                } elseif (self::$avalibleKey[$key] === "int") {
                    $val = intval($val);
                } elseif (self::$avalibleKey[$key] === "float") {
                    $val = round(floatval($val), 4) . "";
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
            $log["x_extra"] = json_decode($log["x_extra"], true);
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
        static $ip;
        if (!$ip) {
            $ip = gethostname();
        }
        return $ip;
    }

    /**
     * 获取当前traceid
     * @return string
     */
    public static function getTraceId()
    {
        //如果没有初始化，那么每次请求都会用一个trace_id
        if (RequestContext::get(self::FIELD_TRACE_ID, "") == ""
            || RequestContext::get(self::FIELD_INIT) == 0) {
            self::generalTraceId();
        }
        return RequestContext::get(self::FIELD_TRACE_ID);
    }

    /**
     * 刷新重新生成当前TraceID
     * @return string
     */
    public static function generalTraceId()
    {
        return RequestContext::set(self::FIELD_TRACE_ID, self::getServerIp() . "_" . getmypid() . "_" . (microtime(true) - 1483200000) . "_" . mt_rand(0, 255));
    }

    /**
     * 获取当前RPCID前段，不含自增值
     * @return string
     */
    public static function getReciveRpcId()
    {
        return RequestContext::get(self::FIELD_RPC_ID);
    }

    /**
     * 获取当前rpcid 包括当前计数
     * @return string
     */
    public static function getCurrentRpcId()
    {
        return RequestContext::get(self::FIELD_RPC_ID) . "." . RequestContext::get(self::FIELD_RPC_ID_SEQ);
    }

    /**
     * 获取下一个RPC ID,发送给被调用方
     * @return string
     */
    public static function getNextRpcId()
    {
        if (RequestContext::get(self::FIELD_INIT) == 0) {
            RequestContext::setMulti([
                self::FIELD_RPC_ID => 1,
                self::FIELD_RPC_ID_SEQ => 1
            ]);
            return "1.1";
        }

        //incr seq
        return RequestContext::get(self::FIELD_RPC_ID) . "." . RequestContext::override(self::FIELD_RPC_ID_SEQ, function($value) {
                return $value ? $value + 1 : 1;
            });
    }

    /**
     * 设置当前服务版本
     * @param $version
     */
    public static function setVersion($version)
    {
        RequestContext::set(self::FIELD_VERSION, $version);
    }

    /**
     * 获取当前服务版本
     * @return string
     */
    public static function getVersion()
    {
        return RequestContext::get(self::FIELD_VERSION, "fend-1.2");
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
        return RequestContext::get(self::FIELD_GRAY, false);
    }

    /**
     * 开启灰度模式
     */
    public static function setGrayStatus($enable = true)
    {
        RequestContext::set(self::FIELD_GRAY, $enable);
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
}
