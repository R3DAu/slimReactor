<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class JwtMiddleware implements MiddlewareInterface
{
    private string $secret;

    public function __construct()
    {
        $jwtSecret = $_ENV['JWT_SECRET'] ?? null;
        if (!$jwtSecret) {
            throw new \RuntimeException("JWT secret not set in environment variables");
        }
        // Decode the secret from environment variables
        $jwtSecret = explode("base64:", $jwtSecret);
        if (count($jwtSecret) !== 2 || empty($jwtSecret[1])) {
            throw new \RuntimeException("Invalid JWT secret format");
        }
        $jwtSecret = base64_decode($jwtSecret[1]);

        $this->secret = $jwtSecret;
    }

    public function process(Request $request, Handler $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse("Missing or malformed Authorization header");
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            $request = $request->withAttribute('jwt', $decoded);
            return $handler->handle($request);
        } catch (\Exception $e) {
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
