<?php
namespace App\Providers;

use DI\ContainerBuilder;
use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\StreamHandler;
use function DI\create;

class LoggerServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $containerBuilder, array $paths = []): void
    {
        $logPath = $paths['logs'] ?? __DIR__ . '/../../Storage/Logs';

        $containerBuilder->addDefinitions([
            Logger::class => create(Logger::class)
                ->constructor('slimReactor')
                ->method('pushHandler', create(StreamHandler::class)
                    ->constructor($logPath . '/app.log', Level::Debug))
                ->method('pushHandler', create(StreamHandler::class)
                    ->constructor($logPath . '/error.log', Level::Error)),
        ]);
    }
}
