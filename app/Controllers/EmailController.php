<?php

namespace App\Controllers;

use App\Services\EmailService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class EmailController extends BaseController
{
    public function send(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $emailService = $this->container->get(EmailService::class);
        $data = $request->getBody()->getContents();
        $data = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->error($response, 'Invalid JSON format', 400);
        }

        $to = $data['to'] ?? null;
        $subject = $data['subject'] ?? null;
        $body = $data['body'] ?? null;
        $options = $data['options'] ?? [];

        if (!$to || !$subject || !$body) {
            return $this->error($response, 'Missing required fields: to, subject, body', 400);
        }

        try {
            $result = $emailService->send($to, $subject, $body, $options);
            if ($result) {
                return $this->success($response, ['message' => 'Email sent successfully']);
            } else {
                return $this->error($response, 'Failed to send email', 500);
            }
        } catch (\Exception $e) {
            return $this->error($response, 'Error sending email: ' . $e->getMessage(), 500);
        }
    }
}