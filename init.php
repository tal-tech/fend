<?php

use Fend\Config;
use Fend\Log\EagleEye;
use Fend\Log\LogAgent;
use Fend\Logger;

ini_set('display_errors', 'off');//开启或关闭PHP异常信息
date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ALL);//异常级别设置

//系统设置不可替换的静态配置
define('FD_DS', DIRECTORY_SEPARATOR);//定制目录符合
define('SYS_ROOTDIR', dirname(__FILE__) . FD_DS);

define('SYS_CACHE', SYS_ROOTDIR . 'app' . FD_DS . 'Cache' . FD_DS);//cache目录，smarty cache，文件缓存放置区域
define('SYS_VIEW', SYS_ROOTDIR . 'app' . FD_DS . 'View' . FD_DS);//http业务模板层

define('ERR_CODE_PATH', SYS_ROOTDIR . 'app' . FD_DS . 'Const' . FD_DS); // 错误码定义路径

//autoload not init tips
//加载autoload
if(!include_once(SYS_ROOTDIR. 'vendor/autoload.php')){
    echo "please run composer update. on project root to init.\n";
    exit;
}

//设置配置，加载路径
\Fend\Config::setConfigPath(SYS_ROOTDIR . 'app/Config');
\Fend\Config::set('fend_err_code_file', SYS_ROOTDIR . 'app' . FD_DS . 'Const' . FD_DS . 'ModuleDefine.php');

//初始化日志
$fend = Config::get("Fend");
EagleEye::disable(!$fend["log"]["trace"]);
LogAgent::setLogPath($fend["log"]["path"]);
Logger::setLogLevel($fend["log"]["level"]);
LogAgent::setFormat($fend["log"]["logFormat"] ?? "json");
LogAgent::setLogRoll($fend["log"]["logRoll"] ?? "day");
LogAgent::setFileNameWithPid($fend["log"]["filenameWithPid"] ?? true);
LogAgent::setLogPrefix($fend["log"]["logPrefix"] ?? "");

