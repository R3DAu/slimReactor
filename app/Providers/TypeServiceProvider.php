<?php

namespace App\Providers;

use App\Controllers\TypeModelController;
use App\Storage\DatabaseStorageHandler;
use App\Storage\StorageManager;
use App\Types\TypeRegistry;
use DI\ContainerBuilder;
use Monolog\Logger;
use PDO;
use Psr\Container\ContainerInterface;
use function DI\autowire;
use function DI\create;

class TypeServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $containerBuilder, array $paths = []): void
    {
        $containerBuilder->addDefinitions([
            PDO::class => function (): PDO {
                $dsn = $_ENV['DB_DSN'] ?? 'mysql:host=localhost;dbname=slimreactor;charset=utf8mb4';
                $username = $_ENV['DB_USER'] ?? 'root';
                $password = $_ENV['DB_PASS'] ?? '';
                return new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            },



            TypeRegistry::class => create(TypeRegistry::class),

            DatabaseStorageHandler::class => autowire(DatabaseStorageHandler::class),

            TypeModelController::class => function (
                Logger $logger,
                ContainerInterface $container,
                TypeRegistry $registry,
                StorageManager $storage
            ) {
                return new TypeModelController($logger, $container, $registry, $storage);
            },

            StorageManager::class => create(StorageManager::class)
                ->constructor([
                    autowire(DatabaseStorageHandler::class)
                ]),
        ]);
    }
}