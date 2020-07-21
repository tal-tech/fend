<?php


namespace Fend\Console;

use Fend\Config;
use Symfony\Component\Console\Application;

/**
 *
 * Class Command
 * @package App\Exec
 */
class Command
{
    private static $app;

    /**
     * 初始化操作
     *
     * Command constructor.
     */
    public function __construct()
    {
        self::$app = new Application("Fend Console Tools", "v1.0.0");
    }

    /**
     * 获取署名
     *
     * @param string $signature
     * @throws \Exception
     */
    public function register($signature = '')
    {
        $config = Config::get('Console');
        $namespace = $config['command_namespace'] ?? '';
        $commandsClass = $this->getConsoleClass($config['command_path'] ?? '');

        if (!empty($commandsClass)) {
            foreach ($commandsClass as $class) {
                $class = $namespace . $class;
                if (!class_exists($class)) {
                    throw new \Exception("{$class}未找到");
                }
                $command = new $class;
                if (!$command instanceof BaseCommand) {
                    throw new \Exception("{$class}必须继承BaseCommand");
                }

                $this->registerRoute($command);
                //如果已经找到要加载的类 不再多次加载 直接执行
                if (!empty($signature) && $command->signature == $signature) {
                    return;
                }
            }
            return;
        }
        throw new \Exception("Console Class 未定义");
    }

    /**
     * 获取对应的脚本
     * @param string $path
     * @return array
     */
    private function getConsoleClass(string $path)
    {
        $return = [];
        if (empty($path)) {
            return $return;
        }
        $files = new \DirectoryIterator($path);
        foreach ($files as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }
            $filename = $file->getFilename();
            if (!\preg_match("/.php$/", $filename)) {
                continue;
            }
            $return[] = substr($filename, 0, -4);
        }
        return $return;
    }

    /**
     * 执行脚本
     * @throws \Exception
     */
    public function run($autoExit = true)
    {
        $this->register();
        self::$app->setAutoExit($autoExit);
        self::$app->run();
    }

    /**
     * 注册执行路由
     * @param BaseCommand $command
     */
    private function registerRoute(BaseCommand $command)
    {
        self::$app->add(new $command)
            ->setName($command->signature)
            ->setDescription($command->desc);
    }

}