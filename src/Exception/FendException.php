<?php

namespace Fend\Exception;

use Throwable;

/**
 * 框架异常基础类
 * 框架所有异常都会继承此类
 * Class FendException
 * @package Fend\Exception
 */
class FendException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}