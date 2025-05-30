<?php
namespace App\Support;

use Psr\Http\Server\RequestHandlerInterface;
use \Psr\Http\Message\ResponseInterface;
use \Psr\Http\Message\ServerRequestInterface;

class MiddlewareStackHandler implements RequestHandlerInterface
{
    private array $middlewares;
    private RequestHandlerInterface $finalHandler;

    public function __construct(array $middlewares, RequestHandlerInterface $finalHandler)
    {
        $this->middlewares = $middlewares;
        $this->finalHandler = $finalHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = array_reduce(
            array_reverse($this->middlewares),
            fn($next, $middleware) => new class($middleware, $next) implements RequestHandlerInterface {
                public function __construct(
                    private $middleware,
                    private RequestHandlerInterface $next
                ) {}

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->middleware->process($request, $this->next);
                }
            },
            $this->finalHandler
        );

        return $handler->handle($request);
    }
}