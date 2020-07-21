<?php

namespace Fend\Queue;

use Fend\Exception\SystemException;
use Throwable;

class Exception extends SystemException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}