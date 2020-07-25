<?php
declare(strict_types=1);

use \PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    public function testContext()
    {
        \Fend\Core\Context::set('Test.name', 'hello');

        $value = \Fend\Core\Context::get('Test.name');

        self::assertEquals('hello', $value);

        \Fend\Core\RequestContext::set('Test.name', 'hello');

        $value = \Fend\Core\RequestContext::get('Test.name');

        self::assertEquals('hello', $value);

        \Fend\Core\RequestContext::override('Test.name', function($value) {
            return 'test';
        });

        $value = \Fend\Core\RequestContext::get('Test.name');

        self::assertEquals('test', $value);
    }

    public function testCoroutine()
    {
        \Swoole\Coroutine::create(function() {

            \Fend\Coroutine\Coroutine::create(function () {
                \Fend\Core\Context::set('Test.name', 'hello');

                $value = \Fend\Core\Context::get('Test.name');

                self::assertEquals('hello', $value);
            });

            \Fend\Coroutine\Coroutine::create(function () {
                $value = \Fend\Core\Context::get('Test.name');
                self::assertEquals(null, $value);
            });

            \Fend\Core\Context::set('Test.name', 'hello');
            \Fend\Core\Context::setGlobal('Test.global', 'global');

            \Fend\Coroutine\Coroutine::create(function () {
                $value = \Fend\Core\Context::get('Test.name');
                self::assertEquals(null, $value);

                $value = \Fend\Core\Context::get('Test.global');
                self::assertEquals('global', $value);
            });
        });

    }
}