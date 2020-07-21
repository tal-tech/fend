<?php

namespace Fend;

class Env
{
    /**
     * 实例
     *
     * @var static
     */
    private static $instance;

    /**
     * 缓存静态配置
     *
     * @var
     */
    private static $config = [];

    /**
     * 单例模式
     *
     * @return Env
     *
     */
    public static function factory()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 加载配置文件
     *
     * @param $paths
     * @param null $names
     *
     * @throws \Exception
     */
    public static function load($paths, $names = null)
    {
        $filename = $paths . ($names ?: '.env');

        if (!file_exists($filename)) {
            throw new \Exception("file : `{$filename}` does not exist.");
        }

        $config = parse_ini_file($filename);

        //ini file format parser fail
        if ($config === FALSE) {
            $error = error_get_last();
            throw new \Exception("Error parser .env file format:" . $error["message"], -9192);
        }

        if ($config) {
            static::$config = self::processConfig($config);
            self::factory()->resetConfigPath();
        }
    }

    /**
     * 获取环境变量值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function env($key, $default = null)
    {
        $value = $this->getEnvironmentVariable($key);

        if ($value === null) {
            return $this->value($default);
        }

        return $value;
    }

    /**
     * 返回给定值的默认值
     *
     * @param $value
     *
     * @return mixed
     */
    protected function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }

    /**
     * 获取环境值
     *
     * @param $name
     *
     * @return array|false|mixed|string|null
     */
    protected function getEnvironmentVariable($name)
    {
        if (isset(static::$config[$name])) {
            return static::$config[$name];
        } else {
            $value = getenv($name);
            return $value === false ? null : $value; // switch getenv customize to null
        }
    }

    /**
     * 处理配置值
     *
     * @param array $config
     *
     * @return array
     */
    protected static function processConfig(array $config = [])
    {
        return array_map(function ($value) {
            switch (strtolower($value)) {
                case 'true':
                case '(true)':
                    return true;
                case 'false':
                case '(false)':
                    return false;
                case 'empty':
                case '(empty)':
                    return '';
                case 'null':
                case '(null)':
                    return null;
                default:
                    return $value;
            }
        }, $config);
    }

    /**
     * 重置配置路径
     *
     * @throws \Exception
     */
    protected function resetConfigPath()
    {
        $name = $this->env('CONFIG_NAME', null);

        if (!$name) {
            return;
        }

        $path = SYS_ROOTDIR . 'app' . FD_DS . 'Config' . FD_DS . $name;

        if (is_dir($path)) {
            Config::setConfigPath($path);
            Config::clean();
        }

        unset($name);
        unset($path);
    }
}

//考虑到还要放到composer.json内加载
//临时先放这里了
if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        return \Fend\Env::factory()->env($key, $default);
    }
}