<?php
namespace App\Storage;

use App\Types\Model;
use App\Types\TypeDefinition;

interface StorageBindingHandler
{
    public function supports(StorageBinding $binding): bool;

    public function fetch(TypeDefinition $type, mixed $id): ?Model;

    public function save(TypeDefinition $type, Model $model): bool;

    public function delete(TypeDefinition $type, mixed $id): bool;

    public function exists(TypeDefinition $type,  mixed $id, mixed $field): bool;
}
