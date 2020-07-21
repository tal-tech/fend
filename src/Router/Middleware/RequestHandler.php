<?php

declare(strict_types=1);


namespace Fend\Router\Middleware;


interface RequestHandler
{
    public function handle($request);
}