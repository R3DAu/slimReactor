<?php
use App\Services\XeroService;
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface;

return function (App $app) {
    $app->get('/xero/connect', function (Request $request, ResponseInterface $response) use ($app) {
        $url = $app->getContainer()->get(XeroService::class)->getAuthorizeUrl();
        return $response
            ->withStatus(302)
            ->withHeader('Location', $url);
    });

    $app->get('/xero/callback', function (Request $request, ResponseInterface $response) use ($app) {
        $params = $request->getQueryParams();
        $code = $params['code'] ?? null;

        if (!$code) {
            $response->getBody()->write("Missing code");
            return $response->withStatus(400);
        }

        $xero = $app->getContainer()->get(XeroService::class);
        $xero->exchangeCodeForToken($code);
        $xero->connectTenant();

        $response->getBody()->write("Xero successfully connected.");
        return $response;
    });
};
