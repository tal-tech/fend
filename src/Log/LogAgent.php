<?php

namespace Fend\Log;

/**
 * Class \Fend\LogAgent\LogAgent
 * 日志统一dump类
 */
class LogAgent
{
    const LOGAGENT_DUMP_LOG_MODE_DIERECT = 0;
    const LOGAGENT_DUMP_LOG_MODE_BUFFER = 1;
    const LOGAGENT_DUMP_LOG_MODE_CHANNEL = 2;


    //最大dump日志阀值，当暂存日志超过这个数马上开始dump
    const MAX_LOG_DUMP_COUNT = 20;

    public static $dumplogmode = 0; //日志落地模式 0 直接写入文件。1 缓存定期写入文件。2 dump到channel 异步写入文件

    public static $channel = null;

    public static $logTempArray = array();

    private static $dumppath = "eagleeye";//default dump path

    //默认json格式输出
    private static $format = "json";

    //文件名带pid
    //建议线上开启，高并发场景下多进程同时写一个文件，内容超过8k，容易导致日志相互覆盖混乱
    private static $filenamePid = true;

    //日志是否滚动
    private static $logRoll = "none";

    //日志前缀
    private static $logPrefix = "";

    /**
     * 日志促使化
     * @param string $logpath
     * @throws \Exception 启动模式和运行模式不匹配时会抛异常
     */
    public static function setLogPath($logpath)
    {
        //log dump path
        self::$dumppath = rtrim($logpath, "/") . "/";

        //direct dump log file
        if (!is_dir(self::$dumppath . "/")) {
            mkdir(self::$dumppath . "/", 0777, 1);
        }
    }

    /**
     * 设置输出日志格式
     * @param string $type json,querystring,common
     * @throws \Exception 传入类型错误
     */
    public static function setFormat($type = "json")
    {
        if ($type === "json") {
            self::$format = "json";
        } elseif ($type === "queryString") {
            self::$format = "queryString";
        } elseif ($type === "export") {
            self::$format = "export";
        } else {
            throw new \Exception("LogAgent wrong format type setup.", 11122);
        }
    }

    /**
     * 日志文件名带进程pid
     * 建议线上开启，高并发场景下多进程同时写一个文件，内容超过8k，容易导致日志相互覆盖混乱
     * @param bool $enable
     */
    public static function setFileNameWithPid($enable = true)
    {
        if ($enable) {
            self::$filenamePid = true;
        } else {
            self::$filenamePid = false;
        }
    }

    /**
     * 设置日志滚动规则
     * @param string $type
     * @throws \Exception
     */
    public static function setLogRoll($type = "none")
    {
        if ($type === "none") {
            self::$logRoll = "none";
        } elseif ($type === "hour") {
            self::$logRoll = "hour";
        } elseif ($type === "day") {
            self::$logRoll = "day";
        } else {
            throw new \Exception("LogAgent wrong log Roll type setup.", 11123);
        }
    }

    /**
     * 设置日志文件前缀
     * @param string $prefix
     */
    public static function setLogPrefix($prefix)
    {
        self::$logPrefix = $prefix;
    }


    /**
     * 更改日志dump 模式
     * @param $mode 0 直接写入文件，1 缓存定期dump 2  swoole下多进程 channel
     * @throws \Exception
     */
    public static function setDumpLogMode($mode)
    {
        if (self::$dumplogmode == 0 && $mode >= 0 && $mode <= 3) {
            self::$dumplogmode = $mode;
        } else {
            return;
        }

        //buffer log
        if ($mode == self::LOGAGENT_DUMP_LOG_MODE_BUFFER) {
            register_shutdown_function(array("\Fend\Log\LogAgent", "memoryDumpLog"));
            return;
        }

        //async log dumper
        if ($mode == self::LOGAGENT_DUMP_LOG_MODE_CHANNEL) {

            //logagent buffer
            if (self::$channel == null) {
                self::$channel = [];
            }
            //not cli mode wrong
            if (php_sapi_name() != "cli") {
                echo "The LogAgent Mode 3 Only Run on Swoole Cli Mode..";

                throw new \Exception("The LogAgent Mode 3 Only Run on Swoole Cli Mode..", 11112);
            }
            return;
        }
    }

    /**
     * 获取日志落地队列状态
     * @return mixed
     */
    public static function getQueueStat()
    {
        //get queue stat
        return count(self::$channel);
    }

    /**
     * 获取生成日志文件名
     * @return string
     */
    public static function getFileName()
    {
        $filename = rtrim(self::$dumppath, "/") . "/" . self::$logPrefix . "-serlogcol-";

        //logRoll
        switch (self::$logRoll) {
            case "none":
                $filename .= "fend";
                break;
            case "day":
                $filename .= date("Y-m-d");
                break;
            case "hour":
                $filename .= date("Y-m-d-H");
                break;
        }

        //文件名内加getmypid
        if (self::$filenamePid) {
            $filename .= "-" . getmypid();
        }

        $filename .= ".log";
        return $filename;
    }

    public static function encodeLog($log)
    {
        switch (self::$format) {
            case "json":
                return json_encode($log);
                break;
            case "queryString":
                return http_build_query($log);
                break;
            case "export":
                $log["x_timestamp"] = date("Y-m-d H:i:s", $log["x_timestamp"]);
                return var_export($log, true);
                break;
        }
    }


    /**
     * 根据不同的日志记录模式
     * 0 直接写入模式
     * 1 内存缓存，溢满dump及shutdown时落地
     * 2 swoole模式，channel收集多进程日志，异步process落地
     * 目前这个设置在changeMode函数
     *
     * @param array $log
     * @throws \Exception 日志工作模式错误会抛出异常
     */
    public static function log($log)
    {
        if (empty($log) && $log === "") {
            return;
        }

        if (self::$dumplogmode == self::LOGAGENT_DUMP_LOG_MODE_DIERECT) {
            file_put_contents(self::getFileName(), self::encodeLog($log) . "\n", FILE_APPEND);
        } elseif (self::$dumplogmode == self::LOGAGENT_DUMP_LOG_MODE_BUFFER) {
            //dump to the memory
            self::$logTempArray[] = $log;
            if (count(self::$logTempArray) > self::MAX_LOG_DUMP_COUNT) {
                self::memoryDumpLog();
            }
        } elseif (self::$dumplogmode == self::LOGAGENT_DUMP_LOG_MODE_CHANNEL) {
            self::$channel[] = $log;
        } else {
            echo "Log Agent不支持的日志落地模式！";
            throw new \Exception("不支持的日志落地模式！", 111111);
        }
    }

    /**
     * 通过内存暂存日志，在日志量大后或shutdown时将日志统一落地
     * 浪费内存，但是io少，可在fpm或cli内使用
     * 此函数建议注册在shutdown函数内
     */
    public static function memoryDumpLog()
    {
        $logStr = "";
        foreach (self::$logTempArray as $logItem) {
            $logStr .= (self::encodeLog($logItem) . "\n");
        }
        self::$logTempArray = array();

        if (empty($logStr)) {
            return;
        }
        file_put_contents(self::getFileName(), $logStr, FILE_APPEND);
    }

    /**
     * 一次性将 channel 中的日志全部 dump 到日志文件中
     * */
    public static function flushChannel()
    {

        if (self::$dumplogmode == self::LOGAGENT_DUMP_LOG_MODE_CHANNEL) {
            $count = 0;
            $bulkContent = '';
            while ($log = array_shift(self::$channel)) {
                $bulkContent = $bulkContent . PHP_EOL . self::encodeLog($log);
                $count++;
                if ($count > self::MAX_LOG_DUMP_COUNT) {
                    file_put_contents(self::getFileName(), $bulkContent, FILE_APPEND);
                    $bulkContent = '';
                    $count = 0;
                }
            }
            if (!empty($bulkContent)) {
                file_put_contents(self::getFileName(), $bulkContent, FILE_APPEND);
            }

        }
    }
}
