<?php

namespace Fend\Exception;

use Throwable;

/**
 * 业务异常类
 * 所有业务抛出的异常继承此类
 * Class BizException
 * @package Fend\Exception
 */
class BizException extends FendException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}