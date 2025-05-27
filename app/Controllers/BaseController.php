<?php

namespace App\Controllers;

use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;

abstract class BaseController
{
    public function __construct(
        Protected Logger $logger,
        Protected ContainerInterface $container
    ) {
        // Initialize any common dependencies or services here
    }

    protected function json(Response $response, array|object $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    protected function success(Response $response, mixed $data = null, string $message = 'OK', int $status = 200): Response
    {
        return $this->json($response, [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function error(Response $response, string $message = 'An error occurred', int $status = 500): Response
    {
        return $this->json($response, [
            'success' => false,
            'message' => $message,
        ], $status);
    }

    protected function notFound(Response $response, string $message = 'Resource not found'): Response
    {
        return $this->error($response, $message, 404);
    }

    protected function unauthorized(Response $response, string $message = 'Unauthorized'): Response
    {
        return $this->error($response, $message, 401);
    }

    protected function forbidden(Response $response, string $message = 'Forbidden'): Response
    {
        return $this->error($response, $message, 403);
    }

    protected function noContent(Response $response): Response
    {
        return $response->withStatus(204);
    }

    protected function notImplemented(Response $response, string $message = 'Not Implemented'): Response
    {
        return $this->error($response, $message, 501);
    }
}
