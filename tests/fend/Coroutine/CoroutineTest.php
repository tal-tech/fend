<?php

declare(strict_types=1);

use Fend\Coroutine\Coroutine;
use \PHPUnit\Framework\TestCase;

class CoroutineTest extends TestCase
{
    public function testCallStatic()
    {
        self::expectException(BadMethodCallException::class);
        Coroutine::notExistFunction();
    }
}