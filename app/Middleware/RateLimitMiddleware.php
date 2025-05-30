<?php
namespace App\Middleware;

use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\SimpleCache\CacheInterface;
use Slim\Psr7\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    protected Logger $logger;
    protected CacheInterface $cache;

    public function __construct(
        protected ContainerInterface $container,
        protected int $limit = 100, // requests
        protected int $windowSeconds = 60, // time window
        protected ?string $scope = null // e.g., "api:*"
    )
    {
        $this->logger = $container->get(Logger::class);
        $this->cache = $container->get(CacheInterface::class);
    }

    public function process(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = $this->getRateLimitKey($request);
        $now = time();

        $data = $this->cache->get($key, ['count' => 0, 'reset' => $now + $this->windowSeconds]);

        if ($now > $data['reset']) {
            $data = ['count' => 0, 'reset' => $now + $this->windowSeconds];
        }

        if ($data['count'] >= $this->limit) {
            $this->logger->warning('Rate limit exceeded', [
                'key' => $key,
                'count' => $data['count'],
                'reset' => $data['reset'],
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);
            return $this->tooManyRequestsResponse($data['reset']);
        }

        $data['count']++;
        $this->cache->set($key, $data, $data['reset'] - $now);

        $response = $handler->handle($request);
        return $response
            ->withHeader('X-RateLimit-Limit', (string)$this->limit)
            ->withHeader('X-RateLimit-Remaining', (string)($this->limit - $data['count']))
            ->withHeader('X-RateLimit-Reset', (string)$data['reset']);
    }

    protected function getRateLimitKey(Request $request): string
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        // If authenticated via JWT or API key, prefer user or key ID
        $user = $request->getAttribute('user');
        $apiKey = $request->getAttribute('api_key');

        $id = $user['id'] ?? ($apiKey['id'] ?? $ip);
        $scopePart = $this->scope ?? '*:*';

        $key = hash('sha256', "{$scopePart}:{$id}");

        return "rate_{$key}";
    }

    protected function tooManyRequestsResponse(int $reset): Response
    {
        $response = new Response(429);
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Rate limit exceeded',
            'reset' => $reset,
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Retry-After', (string)($reset - time()));
    }
}
