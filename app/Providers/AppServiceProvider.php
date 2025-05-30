<?php


namespace App\Providers;

use App\Services\EmailService;
use App\Services\EncryptionService;
use App\Services\HaloService;
use App\Services\HmacService;
use App\Services\JwtService;
use App\Services\PermissionService;
use App\Services\SettingsService;
use App\Types\UserTypeDefinition;
use App\Storage\StorageManager;
use DI\ContainerBuilder;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Psr\SimpleCache\CacheInterface;


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

            CacheInterface::class => function () {
                //$adapter = new ArrayAdapter(); // In-memory for dev
                $adapter = new FilesystemAdapter(); // saves to /var/cache or system temp
                return new Psr16Cache($adapter);
            },

            EmailService::class => function ($c) {
                return new EmailService($c->get(SettingsService::class));
            },

            HaloService::class => function ($c) {
                return new HaloService($c->get(SettingsService::class));
            },
        ]);
    }
}
