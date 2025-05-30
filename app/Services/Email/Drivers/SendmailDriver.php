<?php
namespace App\Services\Email\Drivers;

use App\Contracts\EmailDriverInterface;

class SendmailDriver implements EmailDriverInterface
{
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        $headers = $options['headers'] ?? "From: no-reply@example.com";
        return mail($to, $subject, $body, $headers);
    }
}
