<?php
namespace App\Services;

use App\Contracts\EmailDriverInterface;
use App\Services\Email\Drivers\SendmailDriver;
use App\Services\Email\Drivers\SmtpDriver;
use App\Services\Email\Drivers\MicrosoftGraphDriver;

class EmailService
{
    protected EmailDriverInterface $driver;

    public function __construct(SettingsService $settings)
    {
        $driver = $settings->get('email_driver', 'sendmail');

        $this->driver = match ($driver) {
            'smtp' => new SmtpDriver([
                'smtp_host' => $settings->get('smtp_host'),
                'smtp_port' => $settings->get('smtp_port'),
                'smtp_username' => $settings->get('smtp_username'),
                'smtp_password' => $settings->get('smtp_password'),
                'from_email' => $settings->get('from_email'),
                'from_name' => $settings->get('from_name'),
                'email_html' => (bool)$settings->get('email_html')?? false,
            ]),
            'graph' => new MicrosoftGraphDriver($settings->get('graph_token')),
            default => new SendmailDriver(),
        };
    }

    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        return $this->driver->send($to, $subject, $body, $options);
    }
}
