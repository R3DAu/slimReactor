<?php

namespace App\Types;

use App\Storage\StorageBinding;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ReflectionClass;

class TypeRegistry
{
    /** @var array<string, TypeDefinition> */
    protected array $types = [];

    public function __construct()
    {
        $this->autoRegisterTypes(__DIR__);
    }

    public function register(TypeDefinition $definition): void
    {
        $this->types[$definition->type] = $definition;
    }

    public function get(string $type): TypeDefinition
    {
        if (!isset($this->types[$type])) {
            throw new InvalidArgumentException("Type '{$type}' is not registered in the registry.");
        }

        return $this->types[$type];
    }

    public function all(): array
    {
        return $this->types;
    }

    protected function autoRegisterTypes(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $regex = new RegexIterator($iterator, '/^.+TypeDefinition\.php$/i');

        foreach ($regex as $fileInfo) {
            $filePath = $fileInfo->getRealPath();
            if (!$filePath) continue;

            $relativePath = substr($filePath, strlen(__DIR__));
            $relativePath = strtr($relativePath, ['/' => '\\', '\\' => '\\', '.php' => '']);
            $fqcn = __NAMESPACE__ . rtrim($relativePath, '\\');

            if (!class_exists($fqcn)) continue;

            $reflection = new ReflectionClass($fqcn);
            if ($reflection->isAbstract() || !$reflection->isInstantiable() || !$reflection->isSubclassOf(TypeDefinition::class)) continue;

            /** @var TypeDefinition $instance */
            $instance = $reflection->newInstance();
            $this->register($instance);
        }
    }
}
