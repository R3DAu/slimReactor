<?php

use App\Controllers\TypeModelController;
use Slim\App;

return function (App $app): void {
    $app->get('/api/{type}', [TypeModelController::class, 'index']);
    $app->get('/api/{type}/{id}', [TypeModelController::class, 'show']);
};