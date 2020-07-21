<?php
declare(strict_types=1);

namespace Fend\Router\Middleware;

interface MiddlewareInterface
{
    public function process($request, RequestHandler $handler);
}