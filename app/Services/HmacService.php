<?php


namespace App\Services;

use App\Storage\StorageManager;
use App\Types\ApiClientTypeDefinition;
use App\Types\UserTypeDefinition;
use App\Types\Model;

class HmacService
{
    public function __construct(
        protected StorageManager $storage,
        protected int            $allowedSkewSeconds = 300 // 5 minutes
    )
    {
    }

    public function validate(string $key, string $signature, string $timestamp): ?array
    {
        if (!$key || !$signature || !$timestamp) {
            throw new \RuntimeException('Missing HMAC headers.');
        }

        if (abs(time() - (int)$timestamp) > $this->allowedSkewSeconds) {
            throw new \RuntimeException('Timestamp too old or skewed.');
        }

        $clientModels = $this->storage->fetchAll(new ApiClientTypeDefinition());
        $client = array_filter($clientModels, fn($m) => $m->get('api_key') === $key);
        $client = reset($client);

        if (!$client instanceof Model) {
            throw new \RuntimeException('Invalid API key.');
        }

        $secret = $client->get('api_secret');
        $payload = $timestamp . ":" . $key;
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new \RuntimeException('Invalid HMAC signature.');
        }

        $user = null;
        if ($client->get('user_id')) {
            $userType = new UserTypeDefinition();
            $user = $this->storage->fetch($userType, $client->get('user_id'));
        }

        return [
            'client' => $client,
            'user' => $user
        ];
    }
}
