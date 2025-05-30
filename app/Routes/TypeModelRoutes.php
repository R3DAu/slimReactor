<?php

use App\Controllers\TypeModelController;
use App\Middleware\FlexibleAuthMiddleware;
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
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        $type = $route?->getArgument('type');

        if (!$type) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Invalid or missing type in route',
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $middleware = new FlexibleAuthMiddleware($app->getContainer(), $type);
        return $middleware->process($request, $handler);
    });
};