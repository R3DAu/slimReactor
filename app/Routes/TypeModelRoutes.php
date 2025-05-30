<?php

use App\Controllers\TypeModelController;
use App\Middleware\FlexibleAuthMiddleware;
use App\Middleware\IpFilterMiddleware;
use App\Services\SettingsService;
use Slim\App;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

return function (App $app): void {
    $app->group('/api/{type}', function (\Slim\Routing\RouteCollectorProxy $group) use ($app) {
        $group->post('', [TypeModelController::class, 'store']);
        $group->get('', [TypeModelController::class, 'index']);
        $group->get('/{id}', [TypeModelController::class, 'show']);
        $group->put('/{id}', [TypeModelController::class, 'update']);
        $group->delete('/{id}', [TypeModelController::class, 'delete']);
    })->add(function (Request $request, RequestHandlerInterface $handler) use ($app): ResponseInterface {
        $container = $app->getContainer();
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $type = $route?->getArgument('type');

        if (!$type) {
            return (new \Slim\Psr7\Response())
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json')
                ->withBody((new \Slim\Psr7\Factory\StreamFactory())->createStream(json_encode([
                    'success' => false,
                    'error' => 'Missing type in route',
                ])));
        }

        // Determine action from method
        $method = strtolower($request->getMethod());
        $map = [
            'get'    => 'read',
            'post'   => 'create',
            'put'    => 'update',
            'patch'  => 'update',
            'delete' => 'delete',
        ];
        $action = $map[$method] ?? 'read';
        $permission = "{$type}:{$action}";

        // Instantiate middlewares
        $auth = new FlexibleAuthMiddleware($container, $permission);
        $ipFilter = new IpFilterMiddleware($container->get(SettingsService::class), "{$type}:*");

        // Chain the middlewares
        return (new MiddlewareStackHandler([$ipFilter, $auth], $handler))->handle($request);
    });

    class MiddlewareStackHandler implements RequestHandlerInterface
    {
        private array $middlewares;
        private RequestHandlerInterface $finalHandler;

        public function __construct(array $middlewares, RequestHandlerInterface $finalHandler)
        {
            $this->middlewares = $middlewares;
            $this->finalHandler = $finalHandler;
        }

        public function handle(Request $request): ResponseInterface
        {
            $handler = array_reduce(
                array_reverse($this->middlewares),
                fn($next, $middleware) => new class($middleware, $next) implements RequestHandlerInterface {
                    public function __construct(
                        private $middleware,
                        private RequestHandlerInterface $next
                    ) {}

                    public function handle(Request $request): ResponseInterface
                    {
                        return $this->middleware->process($request, $this->next);
                    }
                },
                $this->finalHandler
            );

            return $handler->handle($request);
        }
    }
};
