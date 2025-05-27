<?php
namespace App\Types;

use App\Types\FieldType;

readonly class TypeDefinition
{
    public function __construct(
        public string $type,
        public array  $fields,  // ['field_name' => FieldType::*]
        public ?\App\Storage\StorageBinding $storage = null
    )
    {
    }

    public function hasField(string $field): bool
    {
        return array_key_exists($field, $this->fields);
    }

    public function getFieldType(string $field): ?FieldType
    {
        return $this->fields[$field] ?? null;
    }
}
