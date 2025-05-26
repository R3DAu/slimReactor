<?php

namespace App\Providers;

use DI\ContainerBuilder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ReflectionClass;
use SplFileInfo;
use function DI\autowire;

class ControllerServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $containerBuilder, array $paths = []): void
    {
        $definitions = [];
        $directory = $paths['controllers'] ?? APPPATH . '/Controllers';
        $namespace = 'App\\Controllers';

        $definitions += $this->discover($directory, $namespace);

        $containerBuilder->addDefinitions($definitions);
    }

    private function discover(string $directory, string $namespace): array
    {
        $definitions = [];
        if (!is_dir($directory)) return $definitions;

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $regex = new RegexIterator($iterator, '/^.+\.php$/i');

        foreach ($regex as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) continue;
            $filePath = $fileInfo->getRealPath();
            if (!$filePath) continue;

            $relativePath = substr($filePath, strlen($directory));
            $relativePath = strtr($relativePath, ['/' => '\\', '\\' => '\\', '.php' => '']);
            $fqcn = rtrim($namespace . $relativePath, '\\');

            if (!class_exists($fqcn)) continue;

            $reflect = new ReflectionClass($fqcn);
            if ($reflect->isAbstract() || !$reflect->isInstantiable()) continue;

            $definitions[$fqcn] = autowire($fqcn);
        }

        return $definitions;
    }
}
