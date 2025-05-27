<?php
namespace App\Config;

use \Slim\App;

return function (App $app) {
    (require ROUTESPATH . '/StatusRoutes.php')($app);
    (require ROUTESPATH . '/TypeModelRoutes.php')($app);
};