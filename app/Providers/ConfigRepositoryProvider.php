<?php
namespace App\Providers;

class ConfigRepositoryProvider
{
    protected array $instances = [];

    public function get(string $classOrKey, ?string $property = null, mixed $default = null): mixed
    {
        // Handle dot notation like app.appName
        if (str_contains($classOrKey, '.')) {
            [$classAlias, $property] = explode('.', $classOrKey, 2);
        } else {
            $classAlias = $classOrKey;
        }

        // Resolve class if not already cached
        if (!isset($this->instances[$classAlias])) {
            $fqcn = 'App\\Config\\' . ucfirst($classAlias);

            if (!class_exists($fqcn)) {
                return $default;
            }

            $this->instances[$classAlias] = new $fqcn();
        }

        if (!$property) {
            return $this->instances[$classAlias];
        }

        return $this->instances[$classAlias]->$property ?? $default;
    }
}