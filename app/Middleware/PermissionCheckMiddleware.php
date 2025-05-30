<?php
namespace App\Middleware;

use App\Services\HmacService;
use App\Services\PermissionService;
use App\Services\JwtService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

readonly class PermissionCheckMiddleware
{
    protected JwtService $jwtService;
    protected HmacService $hmacService;
    protected PermissionService $permissionService;

    public function __construct(
        protected ContainerInterface $container,
        protected string $requiredPermission
    )
    {
        $this->jwtService = $this->container->get(JwtService::class);
        $this->hmacService = $this->container->get(HmacService::class);
        $this->permissionService = $this->container->get(PermissionService::class);
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            //get the token from the Authorization header
            $authHeader = $request->getHeaderLine('Authorization');
            if (empty($authHeader)) {
                //then it's not a JWT request...

                //get the API key from the headers
                $apiKey = $request->getHeaderLine('X-API-KEY');
                $apiSignature = $request->getHeaderLine('X-API-SIGNATURE');
                $apiTimestamp = $request->getHeaderLine('X-API-TIMESTAMP');

                // Then try HMAC
                $hmacResult = $this->hmacService->validate($apiKey, $apiSignature, $apiTimestamp);
                if ($hmacResult !== null) {
                    $client = $hmacResult['client'];
                    $user = $hmacResult['user'];

                    if ($user) {
                        $permissions = $this->permissionService->getPermissionsForUser($user->all());
                    } else {
                        $permissions = $this->permissionService->getPermissionsForApiKey($client->all());
                    }

                    if (!$this->permissionService->hasPermission($permissions, $this->requiredPermission)) {
                        return $this->unauthorized("API key missing permission: {$this->requiredPermission}");
                    }

                    return $handler->handle($request);
                }

            }else if(preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
                $user = $this->jwtService->validateAndFetchUser($token);
                if ($user !== null) {
                    $permissions = $this->permissionService->getPermissionsForUser($user->all());

                    if (!$this->permissionService->hasPermission($permissions, $this->requiredPermission)) {
                        return $this->unauthorized("JWT user missing permission: {$this->requiredPermission}");
                    }

                    return $handler->handle($request);
                }

            } else {
                return $this->unauthorized("Malformed Authorization header");
            }

            return $this->unauthorized("No valid JWT or HMAC authentication provided.");

        } catch (\Throwable $e) {
            print_r($e->getLine());
            return $this->unauthorized("Authorization error: " . $e->getMessage());
        }
    }

    protected function unauthorized(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => $message
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
