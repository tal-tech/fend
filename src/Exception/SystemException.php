<?php

namespace Fend\Exception;

use Throwable;

/**
 * 系统异常基础类
 * 所有关于系统异常继承此类
 * Class SystemException
 * @package Fend\Exception
 */
class SystemException extends FendException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}