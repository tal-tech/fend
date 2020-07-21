<?php
declare(strict_types=1);

use \PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testSetConfig()
    {
        $test = 0;
        try {
            \Fend\Config::setConfigPath(SYS_ROOTDIR . 'example/Config/Db.php');
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);

        \Fend\Config::setConfigPath(SYS_ROOTDIR . 'tests/app/Config');
        $host = \Fend\Config::get('Redis.default.host');

        self::assertEquals('127.0.0.1', $host);

        \Fend\Config::set('Redis', [
            'host' => '127.0.0.1'
        ]);
        $test = 0;
        try {
            $host = \Fend\Config::get('Redis.default.host');
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);

        $test = 0;
        try {
            $host = \Fend\Config::get('Db.default.host');
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);

        \Fend\Config::clean();
        \Fend\Config::loadConfig('Redis', SYS_ROOTDIR . 'app/Config/Redis.php');
        \Fend\Config::loadConfig('Redis', SYS_ROOTDIR . 'app/Config/Redis.php');
        $host = \Fend\Config::get('Redis.default.host');

        self::assertEquals('127.0.0.1', $host);

        \Fend\Config::clean();
        $test = 0;
        try {
            \Fend\Config::loadConfig('Redis',SYS_ROOTDIR . 'app/Config/Redis');
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);
        $test = 0;
        try {
            \Fend\Config::loadConfig('Redis',SYS_ROOTDIR . 'app/Config/Test.php');
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1, $test);

    }

    public function testLocaleConfig()
    {
        \Fend\Config::clean();
        \Fend\Config::setConfigPath(SYS_ROOTDIR . 'app/Config');

        $host = \Fend\Config::get('Redis.default.host');

        self::assertEquals('127.0.0.1', $host);

        $config = \Fend\Config::get('Redis');

        self::assertEquals(true, is_array($config));
    }

    public function resetConfig()
    {
        \Fend\Config::setConfigPath(SYS_ROOTDIR . 'tests/app/Config');
        \Fend\Config::clean();
        \Fend\Config::set('fend_err_code_file', SYS_ROOTDIR . 'App' . FD_DS . 'Const' . FD_DS . 'ModuleDefine.php');

    }
}