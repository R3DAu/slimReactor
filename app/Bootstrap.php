<?php

use App\Middleware\JsonErrorHandler;
use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use App\Config\App as AppConfig;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

return function (App $app): void {
    // Grab the config from the container (bound in index.php)
    $config = $app->getContainer()->get(AppConfig::class);

    // Add Routing Middleware
    $app->addRoutingMiddleware();

    // Register Error Handler
    $errorMiddleware = new ErrorMiddleware(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
        $config->displayErrorDetails,
        true,
        true
    );

    $errorMiddleware->setDefaultErrorHandler(JsonErrorHandler::class);
    $app->add($errorMiddleware);

    // Register routes
    (require CONFIGPATH . '/Routes.php')($app);
};
