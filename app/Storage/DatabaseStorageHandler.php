<?php

namespace App\Storage;

use App\Types\Model;
use App\Types\TypeDefinition;
use PDO;
use PDOException;

class DatabaseStorageHandler implements StorageBindingHandler
{
    public function __construct(protected PDO $pdo) {}

    public function supports(StorageBinding $binding): bool
    {
        return $binding->driver === 'database';
    }

    public function fetch(TypeDefinition $type, mixed $id): ?Model
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$type->storage->tableOrSource} WHERE {$type->storage->idField} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $data = [];
        foreach ($type->storage->mapping as $logical => $physical) {
            $data[$logical] = $row[$physical] ?? null;
        }

        return new Model($type, $data);
    }

    public function fetchAll(TypeDefinition $type): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$type->storage->tableOrSource}");
        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data = [];
            foreach ($type->storage->mapping as $logical => $physical) {
                $data[$logical] = $row[$physical] ?? null;
            }
            $results[] = new Model($type, $data);
        }

        return $results;
    }

    public function save(TypeDefinition $type, Model $model): bool
    {
        $data = $model->all();
        $mapped = [];

        foreach ($type->storage->mapping as $logical => $physical) {
            $value = $data[$logical] ?? null;

            // Convert arrays to JSON for DB storage
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $mapped[$physical] = $value;
        }

        $columns = array_keys($mapped);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = "REPLACE INTO {$type->storage->tableOrSource} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($mapped);
    }

    public function delete(TypeDefinition $type, mixed $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$type->storage->tableOrSource} WHERE {$type->storage->idField} = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function exists(TypeDefinition $type, mixed $id, mixed $field = null): bool
    {
        if($field === null) {
            $field = $type->storage->idField;
        }

        if ($id === null) {
            return false; // Cannot check existence without an ID
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$type->storage->tableOrSource} WHERE {$field} = :id");
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }
}
