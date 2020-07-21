<?php

namespace Fend;

class Config
{
    const LOCALE_PROVIDER = 'localeProvider';

    private static $configPath = SYS_ROOTDIR . "/App/Config/";

    /**
     * @var array
     */
    private static $config = [];

    /**
     * @var
     */
    private static $configProvider = [];

    /**
     * @param $path
     * @throws \Exception
     */
    public static function setConfigPath($path)
    {
        //not found path
        if (!is_dir($path)) {
            throw new \Exception("Config Path not exists :" . $path, 4757);
        }

        self::$configPath = $path;

        if (file_exists(self::$configPath . "/ConfigProvider.php")) {
            self::$configProvider = include(self::$configPath . "/ConfigProvider.php");
        }
    }

    /**
     * 按需加载配置
     * @param string $key 配置文件名前缀（不含扩展名.php）
     * @param string $default 默认返回值，非null 配置项或文件不存在不会抛异常而是返回默认值
     * @return mixed 配置内容
     * @throws \Exception
     */
    public static function get($key, $default = null)
    {
        $xpath = explode('.', $key);
        $configName = $xpath[0];

        $provider = self::$configProvider[$configName] ?? self::LOCALE_PROVIDER;

        return self::$provider($key, $configName, $xpath, $default);
    }

    /**
     * 加载指定路径配置
     * 为了防止被攻击，配置必须php扩展名文件
     * @param string $name 配置名称
     * @param string $path 配置所在绝对路径
     * @param bool $force 是否每次强制刷新配置内容、不推荐频繁使用 true每次都刷新
     * @return array
     * @throws \Exception 配置文件不存在
     */
    public static function loadConfig($name, $path, $force = false)
    {
        if (!$force && isset(self::$config[$name])) {
            return self::$config[$name];
        }

        if (substr($path, -4) !== ".php") {
            throw new \Exception("Config File require *.php:" . $path, 4759);
        }

        //file not exists
        if (!file_exists($path)) {
            throw new \Exception("Config File not found :" . $path, 4758);
        }
        self::$config[$name] = include($path);

        return self::$config[$name];
    }

    /**
     * 人工注入配置
     * @param string $name 配置名称
     * @param $config
     */
    public static function set($name, $config)
    {
        self::$config[$name] = $config;
    }

    /**
     * 清空配置，重新加载
     */
    public static function clean()
    {
        self::$config = [];
    }

    protected static function localeProvider(string $key, string $configName, array $xpath, $default = null, bool $forceUpdate = false)
    {
        //return loaded config
        if (!isset(self::$config[$configName]) || $forceUpdate) {
            //file not exists
            if (!file_exists(self::$configPath . "/" . $configName . ".php")) {
                if ($default !== null) {
                    return $default;
                }
                throw new \Exception("Config File not found :" . self::$configPath . "/" . $configName . ".php", 4758);
            }
            self::$config[$configName] = include(self::$configPath . "/" . $configName . ".php");
        }

        $len = count($xpath);
        $index = 1;
        $config = self::$config[$configName];
        while ($index < $len) {
            if (!isset($config[$xpath[$index]])) {
                if ($default !== null) {
                    return $default;
                }
                throw new \Exception($key . " not exist", 4758);
            }
            $config = $config[$xpath[$index]];
            $index++;
        }

        return $config;
    }

}