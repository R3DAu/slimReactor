<?php

namespace App\Providers;

use App\Config\App;
use DI\ContainerBuilder;

class BaseServiceProvider
{
    /** @var ServiceProviderInterface[] */
    private static array $providers = [
        ConfigServiceProvider::class,
        LoggerServiceProvider::class,
        ControllerServiceProvider::class,
        MiddlewareServiceProvider::class,
        ServiceClassProvider::class,
        TypeServiceProvider::class,
        AppServiceProvider::class,
    ];

    public static function register(ContainerBuilder $containerBuilder, array $paths = []): void
    {
        foreach (self::$providers as $providerClass) {
            /** @var ServiceProviderInterface $provider */
            $provider = new $providerClass();
            $provider->register($containerBuilder, $paths);
        }
    }
}
