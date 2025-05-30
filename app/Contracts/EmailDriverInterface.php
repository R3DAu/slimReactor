<?php

namespace App\Contracts;

interface EmailDriverInterface
{
    public function send(string $to, string $subject, string $body, array $options = []): bool;
}