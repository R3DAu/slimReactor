<?php


namespace App\Providers;

use App\Services\EncryptionService;
use App\Services\HmacService;
use App\Services\JwtService;
use App\Services\PermissionService;
use App\Services\SettingsService;
use App\Types\UserTypeDefinition;
use App\Storage\StorageManager;
use DI\ContainerBuilder;

class AppServiceProvider
{
    public static function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            ConfigRepositoryProvider::class => function () {
                return new ConfigRepositoryProvider();
            },

            EncryptionService::class => function ($container) {
                return new EncryptionService();
            },

            SettingsService::class => function ($container) {
                return new SettingsService(
                    $container->get(StorageManager::class),
                    $container->get(EncryptionService::class),
                    $container->get(ConfigRepositoryProvider::class)
                );
            },

            JwtService::class => function ($container) {
                return new JwtService(
                    $container->get(StorageManager::class),
                    new UserTypeDefinition(),
                    $container->get(SettingsService::class)
                );
            },

            PermissionService::class => function ($container) {
                return new PermissionService(
                    $container->get(StorageManager::class)
                );
            },

            HmacService::class => function ($container) {
                return new HmacService(
                    $container->get(StorageManager::class),
                );
            },
        ]);
    }
}
