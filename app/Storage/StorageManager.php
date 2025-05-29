<?php


namespace App\Storage;

use App\Types\Model;
use App\Types\TypeDefinition;
use RuntimeException;

class StorageManager
{
    /** @var StorageBindingHandler[] */
    protected array $handlers = [];

    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }

    protected function resolveHandler(StorageBinding $binding): StorageBindingHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($binding)) {
                return $handler;
            }
        }

        throw new RuntimeException("No handler found for storage driver '{$binding->driver}'");
    }

    public function fetch(TypeDefinition $type, mixed $id): ?Model
    {
        $binding = $type->storage;
        return $this->resolveHandler($binding)->fetch($type, $id);
    }

    public function fetchAll(TypeDefinition $type, array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $binding = $type->storage;
        return $this->resolveHandler($binding)->fetchAll($type, $filters, $limit, $offset);
    }

    public function save(Model $model): bool
    {
        $type = $model->type;
        $binding = $type->storage;
        return $this->resolveHandler($binding)->save($type, $model);
    }

    public function delete(TypeDefinition $type, mixed $id): bool
    {
        $binding = $type->storage;
        return $this->resolveHandler($binding)->delete($type, $id);
    }

    public function exists(TypeDefinition $type, mixed $id, mixed $field): bool
    {
        $binding = $type->storage;
        return $this->resolveHandler($binding)->exists($type, $id, $field);
    }
}