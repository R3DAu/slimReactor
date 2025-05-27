<?php

namespace App\Controllers;

use App\Types\TypeRegistry;
use App\Storage\StorageManager;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TypeModelController extends BaseController implements CrudControllerInterface
{
    public function __construct(
        Logger $logger,
        ContainerInterface $container,
        protected TypeRegistry $registry,
        protected StorageManager $storage
    ) {
        parent::__construct($logger, $container);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response, string $type): ResponseInterface
    {
        $typeDef = $this->registry->get($type);
        $models = $this->storage->fetchAll($typeDef);

        return $this->success($response, array_map(fn($m) => $m->all(), $models));
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, string $type, string $id): ResponseInterface
    {
        $typeDef = $this->registry->get($type);
        $model = $this->storage->fetch($typeDef, $id);

        if (!$model) {
            return $this->notFound($response);
        }

        return $this->success($response, $model->all());
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response, string $type): ResponseInterface
    {
        $data = (array) json_decode((string) $request->getBody(), true);
        $typeDef = $this->registry->get($type);
        $model = new \App\Types\Model($typeDef, $data);

        $this->storage->save($model);
        return $this->success($response, $model->all(), 'Created', 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, string $type, string $id): ResponseInterface
    {
        $typeDef = $this->registry->get($type);
        $existing = $this->storage->fetch($typeDef, $id);

        if (!$existing) {
            return $this->notFound($response);
        }

        $data = (array) json_decode((string) $request->getBody(), true);
        $merged = array_merge($existing->all(), $data);

        $model = new \App\Types\Model($typeDef, $merged);
        $this->storage->save($model);

        return $this->success($response, $model->all(), 'Updated');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, string $type, string $id): ResponseInterface
    {
        $typeDef = $this->registry->get($type);
        $deleted = $this->storage->delete($typeDef, $id);

        return $deleted ? $this->noContent($response) : $this->notFound($response);
    }
}