<?php
namespace App\Services\Email\Drivers;

use App\Contracts\EmailDriverInterface;
use PHPMailer\PHPMailer\PHPMailer;

class SmtpDriver implements EmailDriverInterface
{
    protected PHPMailer $mailer;

    public function __construct(array $config = [])
    {
        if(empty($config)){
            throw new \Exception('Config array is empty');
        }

        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = $config['smtp_host'];
        $this->mailer->Port = $config['smtp_port'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $config['smtp_username'];
        $this->mailer->Password = $config['smtp_password'];
        $this->mailer->setFrom($config['from_email'], $config['from_name']);
        $this->mailer->isHTML((bool)$config['email_html']); // Set email format to HTML
    }

    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;

            $this->mailer->Body = $body;
            return $this->mailer->send();
        } catch (\Exception $e) {
            return false;
        }
    }
}
