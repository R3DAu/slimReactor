<?php

namespace App\Middleware;

use App\Services\SettingsService;
use Closure;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;

class IpFilterMiddleware
{
    protected Logger $logger;
    protected SettingsService $settingsService;

    public function __construct(
        protected ContainerInterface $container,
        protected string $scope = '*:*' // default fallback scope
    )
    {
        $this->logger = $this->container->get(Logger::class);
        $this->settingsService = $this->container->get(SettingsService::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Use the __invoke method for PSR-15 compatibility
        return $this->__invoke($request, $handler);
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $clientIp = $this->getClientIp($request);

        // Load from settings table
        $ipWhitelistSetting = $this->settingsService->get('ip_whitelists');
        $whitelistMap = json_decode($ipWhitelistSetting ?? '{}', true);

        $scopeCandidates = [
            $this->scope,
            explode(':', $this->scope)[0] . ':*',
            '*:*'
        ];

        foreach ($scopeCandidates as $scopeKey) {
            if (!isset($whitelistMap[$scopeKey])) continue;

            $allowed = $whitelistMap[$scopeKey];

            foreach ($allowed as $ipOrCidr) {
                if ($this->ipMatches($clientIp, $ipOrCidr)) {
                    return $handler->handle($request);
                }
            }
        }

        // Log denied attempt
        $this->logger->warning('IP address denied', [
            'ip' => $clientIp,
            'scope' => $this->scope,
            'route' => $route ? $route->getPattern() : 'unknown',
        ]);

        return $this->unauthorized("IP address '$clientIp' is not allowed for scope '{$this->scope}'.");
    }

    protected function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        return $serverParams['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    protected function ipMatches(string $ip, string $cidrOrIp): bool
    {
        if (strpos($cidrOrIp, '/') !== false) {
            [$subnet, $mask] = explode('/', $cidrOrIp);
            return (ip2long($ip) & ~((1 << (32 - (int)$mask)) - 1)) === (ip2long($subnet) & ~((1 << (32 - (int)$mask)) - 1));
        }

        return $ip === $cidrOrIp;
    }

    protected function unauthorized(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => $message,
        ]));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }
}
