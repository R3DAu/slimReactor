<?php
namespace App\Storage;

use App\Types\Model;
use App\Types\TypeDefinition;

interface StorageBindingHandler
{
    public function supports(StorageBinding $binding): bool;

    public function fetch(StorageBinding $binding, mixed $id): ?Model;

    public function save(StorageBinding $binding, Model $model): bool;

    public function delete(StorageBinding $binding, mixed $id): bool;
}
