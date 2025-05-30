<?php

namespace App\Storage;

use App\Services\EncryptionService;
use App\Types\Model;
use App\Types\SettingTypeDefinition;
use App\Types\TypeDefinition;
use PDO;
use PDOException;

class DatabaseStorageHandler implements StorageBindingHandler
{
    protected EncryptionService $encryption;

    public function __construct(protected PDO $pdo) {
        $this->encryption = new EncryptionService();
    }

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

        //let's decrypt the value if this is a SettingTypeDefinition
        if ($type instanceof SettingTypeDefinition && isset($row['encrypted']) && $row['encrypted']) {
            $row['value'] = $this->encryption->decrypt($row['value']);
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
        $hasEncyption = $type instanceof SettingTypeDefinition;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data = [];
            foreach ($type->storage->mapping as $logical => $physical) {
                // Handle decryption if applicable
                if ($hasEncyption && isset($row['encrypted']) && $row['encrypted'] && $logical == 'value') {
                    $row[$physical] = $this->encryption->decrypt($row[$physical]);
                }

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
        $hasEncyption = $type instanceof SettingTypeDefinition;

        //check if it exists
        if(isset($data[$type->storage->idField]) && $this->exists($type, $data[$type->storage->idField])) {
            // If it exists, we will update it
            $mapped[$type->storage->idField] = $data[$type->storage->idField];
        } else {
            // Otherwise, we will insert a new record
            unset($data[$type->storage->idField]); // Remove ID for insert
        }

        foreach ($type->storage->mapping as $logical => $physical) {
            $value = $data[$logical] ?? null;

            // Convert arrays to JSON for DB storage
            if (is_array($value)) {
                $value = json_encode($value);
            }

            // Handle encryption if applicable
            if ($hasEncyption && ($data['encrypted'] ?? false) && $logical == 'value') {
                $value = $this->encryption->encrypt($value);
            }

            $mapped[$physical] = $value;
        }

        $columns = array_keys($mapped);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        // 1. Insert portion
        $sql = "INSERT INTO {$type->storage->tableOrSource} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";

        // 2. Update portion
        $updateClauses = [];
        foreach ($columns as $column) {
            // Skip primary key if you don't want to update it (optional)
            if ($column === 'id') continue;

            $updateClauses[] = "{$column} = VALUES({$column})";
        }

        $sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $updateClauses);

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
