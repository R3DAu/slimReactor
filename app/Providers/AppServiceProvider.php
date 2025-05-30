<?php


namespace App\Providers;

use App\Services\HmacService;
use App\Services\JwtService;
use App\Services\PermissionService;
use App\Types\UserTypeDefinition;
use App\Storage\StorageManager;
use DI\ContainerBuilder;

class AppServiceProvider
{
    public static function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            JwtService::class => function ($container) {
                return new JwtService(
                    $container->get(StorageManager::class),
                    new UserTypeDefinition()
                );
            },

            PermissionService::class => function ($container) {
                return new PermissionService(
                    $container->get(StorageManager::class)
                );
            },

            HmacService::class => function ($container) {
                return new HmacService(
                    $container->get(StorageManager::class)
                );
            },
        ]);
    }
}
