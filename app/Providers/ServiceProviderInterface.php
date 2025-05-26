<?php
namespace App\Providers;

use DI\ContainerBuilder;

interface ServiceProviderInterface
{
    public function register(ContainerBuilder $containerBuilder, array $paths = []): void;
}
