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

    public function fetch(StorageBinding $binding, mixed $id): ?Model
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$binding->tableOrSource} WHERE {$binding->idField} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $data = [];
        foreach ($binding->mapping as $logical => $physical) {
            $data[$logical] = $row[$physical] ?? null;
        }

        return new Model(new TypeDefinition($binding->tableOrSource, [], $binding), $data);
    }

    public function fetchAll(StorageBinding $binding): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$binding->tableOrSource}");
        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data = [];
            foreach ($binding->mapping as $logical => $physical) {
                $data[$logical] = $row[$physical] ?? null;
            }
            $results[] = new Model(new TypeDefinition($binding->tableOrSource, [], $binding), $data);
        }

        return $results;
    }

    public function save(StorageBinding $binding, Model $model): bool
    {
        $data = $model->all();
        $mapped = [];

        foreach ($binding->mapping as $logical => $physical) {
            $value = $data[$logical] ?? null;

            // Convert arrays to JSON for DB storage
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $mapped[$physical] = $value;
        }

        $columns = array_keys($mapped);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = "REPLACE INTO {$binding->tableOrSource} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($mapped);
    }

    public function delete(StorageBinding $binding, mixed $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$binding->tableOrSource} WHERE {$binding->idField} = :id");
        return $stmt->execute(['id' => $id]);
    }
}
