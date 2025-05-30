<?php

use App\Config\Paths;
use App\Providers\BaseServiceProvider;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Monolog\Logger;
use DI\Bridge\Slim\Bridge as SlimBridge;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

require __DIR__ . '/../vendor/autoload.php';

$paths = new Paths();

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(ROOTPATH);
$dotenv->safeLoad();

// Set up Container
$containerBuilder = new ContainerBuilder();

// 🔐 Native compiled container caching
$containerBuilder->enableCompilation(STORAGEPATH . '/Cache/php-di');

// Register everything via service provider
BaseServiceProvider::register($containerBuilder, [
    'logs' => STORAGEPATH . '/Logs',
    'storage' => STORAGEPATH,
    'cache' => STORAGEPATH . '/Cache',
]);

$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

// ✅ Use Slim-Bridge to create app with container injection
$app = SlimBridge::create($container);

/*$logger = $app->getContainer()->get(Logger::class);
$logger->debug('Slim Framework is starting up...');*/

(require APPPATH . '/Bootstrap.php')($app);

$app->run();
