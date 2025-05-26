<?php

namespace App\Middleware;

use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;
use Nyholm\Psr7\Response;

class JsonErrorHandler implements ErrorHandlerInterface
{
    public function __construct(protected Logger $logger) {}

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $status = $exception instanceof HttpException ? $exception->getCode() : 500;
        $simple = false;

        switch (true) {
            case $exception instanceof HttpNotFoundException:
                $status = 404;
                $message = 'The requested resource was not found.';
                $simple = true;
                break;
            case $exception instanceof HttpMethodNotAllowedException:
                $status = 405;
                $message = 'Method not allowed for this endpoint.';
                $simple = true;
                break;
            case $exception instanceof HttpUnauthorizedException:
                $status = 401;
                $message = 'Unauthorized access.';
                $simple = true;
                break;
            default:
                $message = $exception->getMessage();
                break;
        }

        $error = [
            'success' => false,
            'error' => [
                'message' => $message,
                'type' => get_class($exception),
            ],
        ];

        if ($displayErrorDetails && !$simple) {
            $error['error']['trace'] = $exception->getTrace();
        }

        if ($logErrors) {
            $context = [
                'exception' => $exception,
                'request_uri' => (string) $request->getUri(),
                'method' => $request->getMethod(),
            ];
            $this->logger->error($exception->getMessage(), $context);
        }

        $response = new Response($status);
        $response->getBody()->write(json_encode($error));

        return $response->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}