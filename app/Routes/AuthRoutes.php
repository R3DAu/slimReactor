<?php

namespace App\Routes;

use App\Middleware\FlexibleAuthMiddleware;
use App\Middleware\HmacAuthMiddleware;
use App\Middleware\JwtMiddleware;
use App\Middleware\PermissionCheckMiddleware;
use Slim\App;
use App\Controllers\AuthController;

return function (App $app) {
    $app->post('/auth/login', [AuthController::class, 'login']);
    $app->get('/me', [AuthController::class, 'me'])
        ->add(new JwtMiddleware($app->getContainer()))
        ->add(new PermissionCheckMiddleware( $app->getContainer(),'self:read'));

    $app->get('/m2m', [AuthController::class, 'm2m'])
        ->add(new HmacAuthMiddleware($app->getContainer()))
        ->add(new PermissionCheckMiddleware( $app->getContainer(),'self:read'));

    $app->get('/flex', [AuthController::class, 'flex'])
        ->add(new FlexibleAuthMiddleware($app->getContainer(), 'self'));
};



