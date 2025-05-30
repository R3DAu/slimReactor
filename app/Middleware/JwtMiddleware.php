<?php

namespace App\Middleware;

use App\Services\JwtService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class JwtMiddleware implements MiddlewareInterface
{
    protected JwtService $jwtService;

    public function __construct(
        protected ContainerInterface $container,
    )
    {
        $this->jwtService = $this->container->get(JwtService::class);
    }

    public function process(Request $request, Handler $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse("Missing or malformed Authorization header");
        }

        $token = $matches[1];

        try {
            $user = $this->jwtService->validateAndFetchUser($token);
            $decoded = $this->jwtService->decodeToken($token);

            $request = $request->withAttribute('user', $user);
            $request = $request->withAttribute('jwt', $decoded);

            return $handler->handle($request);
        } catch (\Throwable $e) {
            return $this->unauthorizedResponse("Invalid token: " . $e->getMessage());
        }
    }

    protected function unauthorizedResponse(string $message): ResponseInterface
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
