<?php
namespace App\Providers;

use DI\ContainerBuilder;

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $containerBuilder, array $paths = []): void
    {
        $containerBuilder->addDefinitions([
            \App\Config\App::class => \DI\autowire(\App\Config\App::class),
        ]);
    }
}
