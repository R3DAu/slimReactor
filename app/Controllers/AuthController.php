<?php

namespace App\Controllers;

use App\Types\Model;
use App\Types\TypeRegistry;
use App\Storage\StorageManager;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use \Psr\Container\ContainerInterface;

class AuthController extends BaseController
{
    public function __construct(
        protected Logger             $logger,
        protected ContainerInterface $container,
        protected TypeRegistry       $registry,
        protected StorageManager     $storage
    )
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(ROOTPATH);
        $dotenv->safeLoad();

        parent::__construct($logger, $container);
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $tokenData = $request->getAttribute('jwt');

        return $this->success($response, [
            'user' => [
                'id' => $tokenData->sub,
                'email' => $tokenData->email,
                'roles' => $tokenData->roles ?? []
            ]
        ]);
    }

    public function m2m(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('api_user');

        return $this->success($response, [
            'user' => [
                'id' => $user->get('id'),
                'email' => $user->get('email'),
                'roles' => $user->get('roles') ?? []
            ]
        ]);
    }

    public function flex(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('api_user');

        //get the user from API key or JWT
        if (!$user) {
            $jwt = $request->getAttribute('jwt');
            if ($jwt) {
                $user = $this->storage->fetch($this->registry->get('user'), $jwt->sub);
            }
        }

        return $this->success($response, [
            'user' => [
                'id' => $user->get('id'),
                'email' => $user->get('email'),
                'roles' => $user->get('roles') ?? []
            ]
        ]);
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->error($response, 'Email and password are required', 400);
        }

        // ✅ Get the proper TypeDefinition from the registry
        $type = $this->registry->get('user');

        // ✅ Fetch all users, you might want to optimize this to query by email directly
        $users = $this->storage->fetchAll($type);
        $user = collect($users)->first(fn(Model $u) => $u->get('email') === $email);

        $user = array_filter($users, fn($u) => $u->get('email') === $email);
        $user = reset($user);

        if (!$user || !password_verify($password, $user->get('password'))) {
            return $this->unauthorized($response, 'Invalid credentials');
        }

        $payload = [
            'sub' => $user->get('id'),
            'email' => $user->get('email'),
            'roles' => $user->get('roles'),
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        $this->logger->info('User logged in', ['email' => $email, 'userId' => $user->get('id')]);
        $jwtSecret =  $_ENV['JWT_SECRET'] ?? throw new \RuntimeException('JWT secret is not defined');
        $jwtSecret = explode('base64:', $jwtSecret)[1] ?? $jwtSecret; // Handle base64 encoded secret

        if (empty($jwtSecret)) {
            return $this->error($response, 'JWT secret is not defined', 500);
        }
        $jwtSecret = base64_decode($jwtSecret);

        $token = JWT::encode($payload, $jwtSecret, 'HS256');

        return $this->success($response, [
            'token' => $token,
             'user' => [
                'id' => $user->get('id'),
                'email' => $user->get('email'),
                'roles' => $user->get('roles'),
             ]
        ]);
    }
}

