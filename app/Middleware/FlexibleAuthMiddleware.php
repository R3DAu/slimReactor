<?php

namespace App\Middleware;

use App\Services\HmacService;
use App\Services\JwtService;
use App\Services\PermissionService;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;

class FlexibleAuthMiddleware implements MiddlewareInterface
{
    protected JwtService $jwtService;
    protected HmacService $hmacService;
    protected PermissionService $permissionService;
    protected Logger $logger;

    public function __construct(
        protected ContainerInterface $container,
        protected string $requiredDomain = '' // e.g. 'admin', 'user', etc.
    )
    {
        $this->jwtService = $this->container->get(JwtService::class);
        $this->hmacService = $this->container->get(HmacService::class);
        $this->permissionService = $this->container->get(PermissionService::class);
        $this->logger = $this->container->get(Logger::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = null;
        $apiKeyRecord = null;

        $authHeader = $request->getHeaderLine('Authorization');

        // Prefer JWT if Authorization header is present
        if (str_starts_with(strtolower($authHeader), 'bearer')) {
            try {

                // Extract token from Authorization header
                if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                    $token = $matches[1];
                } else {
                    return $this->unauthorized("Malformed Authorization header");
                }

                $user = $this->jwtService->validateAndFetchUser($token) ?? null;
                $decoded = $this->jwtService->decodeToken($token);
                $request = $request->withAttribute('jwt', $decoded)->withAttribute('user', $user);
            } catch (\Throwable $e) {
                // Log the error
                $this->logger->error('JWT validation failed', [
                    'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                    'headers' => $request->getHeaders(),
                    'error' => $e->getMessage(),
                ]);
                return $this->unauthorized($e->getMessage());
            }
        }
        // Fallback to HMAC
        elseif ($request->hasHeader('X-API-KEY') && $request->hasHeader('X-API-SIGNATURE')) {
            try {
                //get key, signature, and timestamp from headers
                $key = $request->getHeaderLine('X-API-KEY');
                $signature = $request->getHeaderLine('X-API-SIGNATURE');
                $timestamp = $request->getHeaderLine('X-API-TIMESTAMP');

                $result = $this->hmacService->validate($key, $signature, $timestamp);
                $apiKeyRecord = $result['client'];
                $user = $result['user'];
                $request = $request
                    ->withAttribute('api_client', $apiKeyRecord)
                    ->withAttribute('api_user', $user);
            } catch (\Throwable $e) {
                // Log the error
                $this->logger->error('HMAC validation failed', [
                    'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                    'headers' => $request->getHeaders(),
                    'error' => $e->getMessage(),
                ]);

                return $this->unauthorized($e->getMessage());
            }
        } else {
            //log result
            $this->logger->warning('Unauthorized access attempt', [
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                'headers' => $request->getHeaders(),
            ]);

            return $this->unauthorized("Missing Authorization or HMAC headers");
        }

        // Determine permission based on HTTP method
        $method = strtolower($request->getMethod());
        $action = match ($method) {
            'get'    => 'read',
            'post'   => 'create',
            'put', 'patch' => 'update',
            'delete' => 'delete',
            default  => 'read'
        };

        $requiredPermission = "{$this->requiredDomain}:{$action}";

        // Resolve permissions
        $permissions = $user
            ? $this->permissionService->getPermissionsForUser($user->all())
            : $this->permissionService->getPermissionsForApiKey($apiKeyRecord->all());

        // Check permission
        if (!$this->permissionService->hasPermission($permissions, $requiredPermission)) {
            // Log unauthorized access
            $this->logger->warning('Unauthorized access attempt', [
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                'user_id' => $user->id ?? null,
                'api_key_id' => $apiKeyRecord->id ?? null,
                'required_permission' => $requiredPermission,
                'permissions' => $permissions,
            ]);
            return $this->unauthorized("Insufficient permissions for $requiredPermission");
        }

        return $handler->handle($request);
    }

    protected function unauthorized(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => $message,
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
