<?php
namespace App\Services;

class EncryptionService
{
    protected string $key;

    public function __construct()
    {
        $key = $_ENV['ENCRYPTION_KEY'] ?? null;
        if (!$key) throw new \RuntimeException("Missing ENCRYPTION_KEY in .env");
        $this->key = hash('sha256', $key, true); // 256-bit key
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plaintext, 'aes-256-cbc', $this->key, 0, $iv);
        return base64_encode($iv . $cipher);
    }

    public function decrypt(string $encoded): string
    {
        $data = base64_decode($encoded);
        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);
        return openssl_decrypt($cipher, 'aes-256-cbc', $this->key, 0, $iv);
    }
}
