<?php

namespace App\Middleware;

use App\Services\HmacService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;

class HmacAuthMiddleware implements MiddlewareInterface
{
    protected HmacService $hmacService;

    public function __construct(
        protected ContainerInterface $container
    )
    {
        $this->hmacService = $this->container->get(HmacService::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $headers = $request->getHeaders();
        $key     = $headers['X-API-KEY'][0] ?? null;
        $sig     = $headers['X-API-SIGNATURE'][0] ?? null;
        $ts      = $headers['X-API-TIMESTAMP'][0] ?? null;

        try {
            $result = $this->hmacService->validate($key, $sig, $ts);

            $request = $request
                ->withAttribute('api_client', $result['client'])
                ->withAttribute('api_user', $result['user']);

            return $handler->handle($request);
        } catch (\Throwable $e) {
            return $this->unauthorized($e->getMessage());
        }
    }

    protected function unauthorized(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => $message
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
