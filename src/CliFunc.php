<?php
namespace Fend;

/**
 * Class CliFunc
 * cli 命令行下及swoole下公用函数
 */

class CliFunc
{

    /**
     * tal自定义服务进程名设置，默认将当前进程名称改为tal_$prefix:typenam如tal_baseserver:master
     * @param $prefix
     * @param $typeName
     */
    public static function setProcessName($prefix, $typeName)
    {
        if (empty($_SERVER['SSH_AUTH_SOCK']) || stripos($_SERVER['SSH_AUTH_SOCK'], 'apple') === false) {
            swoole_set_process_name("tal_" . $prefix . ":" . $typeName);
        }
    }

    /**
     * 通过shell命令获取当前ip列表，并找出a\b\c类ip地址。
     * 用于自动识别当前服务器ip地址
     * 建议使用root权限服务使用
     * @param string $localIP
     * @return string
     * @throws \RuntimeException
     */
    public static function getLocalIp($localIP = "0.0.0.0")
    {
        if (function_exists("swoole_get_local_ip")) {

            $serverIps = swoole_get_local_ip();
            $patternArray = array(
                '10\.',
                '172\.1[6-9]\.',
                '172\.2[0-9]\.',
                '172\.31\.',
                '192\.168\.'
            );

            foreach ($serverIps as $serverIp) {
                // 匹配内网IP
                if (preg_match('#^' . implode('|', $patternArray) . '#', $serverIp)) {
                    return trim($serverIp);
                }
            }
            //can't found ok use first
            return $localIP;
        }
        throw new \RuntimeException('Unsupported');
    }

}
