#!/usr/bin/env php
<?php

use App\Console\ConsoleKernel;
use DI\ContainerBuilder;

require __DIR__ . '/vendor/autoload.php';

// Load env and paths
$paths = new App\Config\Paths();
$dotenv = Dotenv\Dotenv::createImmutable(ROOTPATH);
$dotenv->safeLoad();

// Build container
$builder = new ContainerBuilder();
\App\Providers\BaseServiceProvider::register($builder, [
    'logs' => $paths->logsDirectory,
    'storage' => $paths->storageDirectory,
    'cache' => $paths->cacheDirectory,
]);
$container = $builder->build();

// Run CLI kernel
$kernel = $container->get(ConsoleKernel::class);
$kernel->handle($argv);
