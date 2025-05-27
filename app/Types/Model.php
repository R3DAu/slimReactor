<?php
namespace App\Types;

use InvalidArgumentException;

class Model
{
    public function __construct(
        public TypeDefinition $type,
        protected array       $data = []
    )
    {
        $this->validate();
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        if (!$this->type->hasField($key)) {
            throw new InvalidArgumentException("Field '{$key}' is not defined for type '{$this->type->type}'");
        }
        $this->data[$key] = $value;
    }

    public function all(): array
    {
        return $this->data;
    }

    protected function validate(): void
    {
        foreach ($this->data as $key => $value) {
            if (!$this->type->hasField($key)) {
                throw new InvalidArgumentException("Unexpected field '{$key}' in model of type '{$this->type->type}'");
            }
            // Later: validate based on FieldType
        }
    }
}
