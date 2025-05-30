<?php

namespace App\Routes;

use App\Middleware\FlexibleAuthMiddleware;
use App\Middleware\HmacAuthMiddleware;
use App\Middleware\IpFilterMiddleware;
use App\Middleware\JwtMiddleware;
use App\Middleware\PermissionCheckMiddleware;
use App\Middleware\RateLimitMiddleware;
use Slim\App;
use App\Controllers\AuthController;

return function (App $app) {
    $app->post('/auth/login', [AuthController::class, 'login']);
    $app->get('/me', [AuthController::class, 'me'])
        ->add(new RateLimitMiddleware($app->getContainer(), 100, 60, 'self:read'))
        ->add(new IpFilterMiddleware($app->getContainer()))
        ->add(new JwtMiddleware($app->getContainer()))
        ->add(new PermissionCheckMiddleware( $app->getContainer(),'self:read'));

    $app->get('/m2m', [AuthController::class, 'm2m'])
        ->add(new RateLimitMiddleware($app->getContainer(), 100, 60, 'self:read'))
        ->add(new IpFilterMiddleware($app->getContainer()))
        ->add(new HmacAuthMiddleware($app->getContainer()))
        ->add(new PermissionCheckMiddleware( $app->getContainer(),'self:read'));

    $app->get('/flex', [AuthController::class, 'flex'])
        ->add(new RateLimitMiddleware($app->getContainer(), 100, 60, 'self:read'))
        ->add(new IpFilterMiddleware($app->getContainer()))
        ->add(new FlexibleAuthMiddleware($app->getContainer(), 'self'));
};



