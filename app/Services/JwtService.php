<?php


namespace App\Services;

use App\Storage\StorageManager;
use App\Types\Model;
use App\Types\TypeDefinition;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;

class JwtService
{
    protected string $secret;

    public function __construct(
        protected StorageManager $storage,
        protected TypeDefinition $userType,
        protected SettingsService $settingsService
    )
    {
        $jwtSecret = $this->settingsService->get('JWT_SECRET') ?? null;
        if (!$jwtSecret || !str_starts_with($jwtSecret, 'base64:')) {
            throw new RuntimeException("Invalid JWT secret format");
        }

        $base64Part = explode("base64:", $jwtSecret)[1] ?? null;
        $this->secret = base64_decode($base64Part);
    }

    public function decodeToken(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, 'HS256'));
    }

    public function validateAndFetchUser(string $token): ?Model
    {
        $decoded = $this->decodeToken($token);
        $userId = $decoded->sub ?? null;

        if (!$userId) {
            throw new RuntimeException("Missing 'sub' claim in JWT");
        }

        $user = $this->storage->fetch($this->userType, $userId);
        if (!$user || !$user->get('is_active')) {
            throw new RuntimeException("User not found or inactive");
        }

        return $user;
    }
}
