<?php

namespace Fend\Router;

class RouterException extends \Exception
{
    public function __construct($msg, $code = 0)
    {
        parent::__construct($msg, $code);
    }
}