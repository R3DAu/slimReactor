<?php
namespace App\Services\Email\Drivers;

use App\Contracts\EmailDriverInterface;

class MicrosoftGraphDriver implements EmailDriverInterface
{
    protected string $token;

    public function __construct(string $accessToken = '')
    {
        $this->token = $accessToken;
    }

    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        $payload = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'Text',
                    'content' => $body,
                ],
                'toRecipients' => [
                    ['emailAddress' => ['address' => $to]],
                ],
            ],
            'saveToSentItems' => true,
        ];

        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://graph.microsoft.com/v1.0/me/sendMail', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        return $response->getStatusCode() === 202;
    }
}
