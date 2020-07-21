<?php

declare(strict_types=1);


namespace Fend\Router\Middleware;


use Fend\Di;
use Fend\Exception\FendException;

class HttpRequestHandler implements RequestHandler
{

    /**
     * @var array
     */
    private $middlewares;

    /**
     * @var
     */
    private $coreHandler;

    /**
     * @var int
     */
    private $offset = 0;

    public function __construct(array $middlewares, $coreHandler)
    {
        $this->middlewares = $middlewares;
        $this->coreHandler = $coreHandler;
    }

    /**
     * @param $request
     * @return mixed
     * @throws FendException
     */
    public function handle($request)
    {
        if (! isset($this->middlewares[$this->offset]) && ! empty($this->coreHandler)) {
            $handler = $this->coreHandler;
        } else {
            $handler = $this->middlewares[$this->offset];
            is_string($handler) && $handler = new $handler();
        }
        if (! method_exists($handler, 'process')) {
            throw new FendException(sprintf('Invalid middleware, it have to provide a process() method.'));
        }
        return $handler->process($request, $this->next());
    }

    protected function next()
    {
        $this->offset ++;
        return $this;
    }
}