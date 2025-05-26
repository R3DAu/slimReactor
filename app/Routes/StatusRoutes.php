<?php
namespace App\Routes;

use Slim\App;

return function (App $app) {
    $app->get('/status', [\App\Controllers\StatusController::class, 'index']);
    $app->get('/healthcheck', [\App\Controllers\StatusController::class, 'healthCheck']);
};