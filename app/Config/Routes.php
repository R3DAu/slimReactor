<?php
namespace App\Config;

use \Slim\App;

return function (App $app) {
    (require ROUTESPATH . '/StatusRoutes.php')($app);
    (require ROUTESPATH . '/AuthRoutes.php')($app);

    // THIS MUST BE LAST - IT HANDLES DYNAMIC TYPE MODELS
    (require ROUTESPATH . '/TypeModelRoutes.php')($app);
};