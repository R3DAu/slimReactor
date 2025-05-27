<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface CrudControllerInterface
{
    public function index(ServerRequestInterface $request, ResponseInterface $response, string $type): ResponseInterface;

    public function show(ServerRequestInterface $request, ResponseInterface $response, string $type, string $id): ResponseInterface;

    public function store(ServerRequestInterface $request, ResponseInterface $response, string $type): ResponseInterface;

    public function update(ServerRequestInterface $request, ResponseInterface $response, string $type, string $id): ResponseInterface;

    public function delete(ServerRequestInterface $request, ResponseInterface $response, string $type, string $id): ResponseInterface;
}
