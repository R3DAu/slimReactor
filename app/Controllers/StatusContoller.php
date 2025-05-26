<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StatusController extends BaseController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->success($response, [
            'message' => 'API is running',
            'timestamp' => time(),
        ]);
    }

    public function healthCheck(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Here you can add more complex health checks if needed
        return $this->json($response, [
            'status' => 'healthy',
            'message' => 'All systems operational',
            'timestamp' => time(),
        ]);
    }
}