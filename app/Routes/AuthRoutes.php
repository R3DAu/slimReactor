<?php

namespace App\Routes;

use App\Middleware\JwtMiddleware;
use Slim\App;
use App\Controllers\AuthController;

return function (App $app) {
    $app->post('/auth/login', [AuthController::class, 'login']);
    $app->get('/me', [AuthController::class, 'me'])->add(new JwtMiddleware());
};



