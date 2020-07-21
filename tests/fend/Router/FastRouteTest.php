<?php

use Fend\Config;
use PHPUnit\Framework\TestCase;

class FastRouteTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testFastRoute()
    {
        \Fend\Di::factory()->setRequest( new \Fend\Request("fpm"));
        \Fend\Di::factory()->setResponse( new \Fend\Response("fpm"));

        shell_exec(sprintf('rm -rf %s', SYS_CACHE . '*.cache'));

        $config = Config::get("Router");
        $dispatcher = new \Fend\Router\Dispatcher($config);

        $result = $dispatcher->dispatch('www.fend.com', 'GET', '/index');
        self::assertNotEmpty($result);

        $test = 0;
        try {
            $dispatcher->dispatch('www.fend.com', 'POST', '/exception');
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1,$test);

        $test = 0;
        try {
            $dispatcher->dispatch('www.fend.com', 'POST', '/test');
        } catch (\Exception $e) {
            $test = 1;
        }
        self::assertEquals(1,$test);

        $test = 0;
        try {
            $dispatcher->dispatch('www.fend.com', 'POST', '/class');
        } catch (\Fend\Router\RouterException $e) {
            $test = 1;
        }
        self::assertEquals(1,$test);

        $test = 0;
        try {
            $dispatcher->dispatch('www.fend.com', 'POST', '/class1');
        } catch (\Fend\Router\RouterException $e) {
            $test = 1;
        }
        self::assertEquals(1,$test);

        $test = 0;
        try {
            $dispatcher->dispatch('www.fend.com', 'POST', '/class2');
        } catch (\Fend\Router\RouterException $e) {
            $test = 1;
        }
        self::assertEquals(1,$test);

        $test = 0;
        try {
            $dispatcher->dispatch('www.fend.com', 'POST', '/asdfasdf');
        } catch (\Fend\Router\RouterException $e) {
            $test = 1;
        }
        self::assertEquals(1,$test);
    }
}