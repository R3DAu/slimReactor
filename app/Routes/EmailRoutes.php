<?php

use App\Middleware\FlexibleAuthMiddleware;
use App\Middleware\IpFilterMiddleware;
use App\Middleware\RateLimitMiddleware;
use \App\Controllers\EmailController;
use Slim\Routing\RouteCollectorProxy;
use Slim\App;

return function (App $app): void {
    $app->group('/api/email', function (RouteCollectorProxy $group) use ($app) {
        $group->post('', [EmailController::class, 'send']);
    })  ->add(new RateLimitMiddleware($app->getContainer()), 100, 60, 'email:*')
        ->add(new IpFilterMiddleware($app->getContainer()))
        ->add(new FlexibleAuthMiddleware($app->getContainer(), 'email'));
};