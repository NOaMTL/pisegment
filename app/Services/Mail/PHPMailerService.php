<?php

declare(strict_types=1);

namespace App\Services\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * PHPMailer Implementation - 100% standalone (no Laravel)
 *
 * Dependencies: composer require phpmailer/phpmailer
 *
 * Usage:
 * $mailer = new PHPMailerService([
 *     'host' => 'smtp.gmail.com',
 *     'port' => 587,
 *     'username' => 'your@email.com',
 *     'password' => 'password',
 *     'encryption' => 'tls',
 *     'from' => ['email' => 'noreply@example.com', 'name' => 'My App'],
 * ]);
 */
class PHPMailerService implements MailServiceInterface
{
    private array $config;

    private $logger = null;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls', // tls, ssl, or null
            'from' => ['email' => 'noreply@example.com', 'name' => 'Application'],
            'debug' => false,
        ], $config);
    }

    /**
     * Send a simple email
     *
     * @param  string|array  $to  Single recipient or array of recipients
     */
    public function send(string|array $to, string $subject, string $body, array $options = []): bool
    {
        try {
            $mail = $this->createMailer();

            // Add recipients
            $this->addRecipients($mail, $to);

            // Subject & Body
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            // Apply options
            $this->applyOptions($mail, $options);

            // Send
            $result = $mail->send();

            if ($result) {
                $this->log('info', 'Email sent successfully', [
                    'to' => is_array($to) ? $to : [$to],
                    'subject' => $subject,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('error', 'Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send email using a Mailable class (Laravel compatibility)
     *
     * Note: This requires Laravel Mailable, won't work in pure PHP
     */
    public function sendMailable(string|array $to, $mailable): bool
    {
        throw new \Exception('sendMailable() requires Laravel. Use send() method instead.');
    }

    /**
     * Send email to multiple recipients
     */
    public function sendBulk(array $recipients, string $subject, string $body, array $options = []): bool
    {
        $success = true;

        foreach ($recipients as $recipient) {
            if (! $this->send($recipient, $subject, $body, $options)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Queue an email (not supported in standalone mode)
     *
     * Falls back to immediate sending
     */
    public function queue(string|array $to, string $subject, string $body, array $options = []): bool
    {
        $this->log('warning', 'Queue not supported in PHPMailer, sending immediately');

        return $this->send($to, $subject, $body, $options);
    }

    /**
     * Create a mail builder
     */
    public function builder(): MailBuilder
    {
        return new MailBuilder($this);
    }

    /**
     * Set custom logger
     */
    public function setLogger(callable $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Create PHPMailer instance
     */
    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $this->config['host'];
        $mail->Port = $this->config['port'];

        if (! empty($this->config['username'])) {
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
        }

        if ($this->config['encryption']) {
            $mail->SMTPSecure = $this->config['encryption'];
        }

        // Debug mode
        if ($this->config['debug']) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        }

        // Default from
        $from = $this->config['from'];
        $mail->setFrom($from['email'], $from['name'] ?? '');

        $mail->CharSet = 'UTF-8';

        return $mail;
    }

    /**
     * Add recipients to PHPMailer
     */
    private function addRecipients(PHPMailer $mail, string|array $recipients): void
    {
        $recipientList = is_array($recipients) ? $recipients : [$recipients];

        foreach ($recipientList as $recipient) {
            if (is_array($recipient)) {
                $mail->addAddress($recipient['email'], $recipient['name'] ?? '');
            } else {
                $mail->addAddress($recipient);
            }
        }
    }

    /**
     * Apply options to PHPMailer
     */
    private function applyOptions(PHPMailer $mail, array $options): void
    {
        // CC
        if (isset($options['cc']) && ! empty($options['cc'])) {
            $cc = is_array($options['cc']) ? $options['cc'] : [$options['cc']];
            foreach ($cc as $ccRecipient) {
                if (is_array($ccRecipient)) {
                    $mail->addCC($ccRecipient['email'], $ccRecipient['name'] ?? '');
                } else {
                    $mail->addCC($ccRecipient);
                }
            }
        }

        // BCC
        if (isset($options['bcc']) && ! empty($options['bcc'])) {
            $bcc = is_array($options['bcc']) ? $options['bcc'] : [$options['bcc']];
            foreach ($bcc as $bccRecipient) {
                if (is_array($bccRecipient)) {
                    $mail->addBCC($bccRecipient['email'], $bccRecipient['name'] ?? '');
                } else {
                    $mail->addBCC($bccRecipient);
                }
            }
        }

        // Attachments
        if (isset($options['attachments']) && is_array($options['attachments']) && ! empty($options['attachments'])) {
            foreach ($options['attachments'] as $attachment) {
                if (is_string($attachment)) {
                    $mail->addAttachment($attachment);
                } elseif (is_array($attachment)) {
                    if (isset($attachment['type']) && $attachment['type'] === 'data') {
                        $mail->addStringAttachment(
                            $attachment['data'],
                            $attachment['name'],
                            'base64',
                            $attachment['options']['mime'] ?? 'application/octet-stream'
                        );
                    } else {
                        $name = $attachment['options']['as'] ?? basename($attachment['path']);
                        $mail->addAttachment($attachment['path'], $name);
                    }
                }
            }
        }

        // From (override default)
        if (isset($options['from'])) {
            $from = $options['from'];
            if (is_array($from)) {
                $mail->setFrom($from['email'], $from['name'] ?? '');
            } else {
                $mail->setFrom($from);
            }
        }

        // Reply-To
        if (isset($options['replyTo'])) {
            $replyTo = $options['replyTo'];
            if (is_array($replyTo)) {
                $mail->addReplyTo($replyTo['email'], $replyTo['name'] ?? '');
            } else {
                $mail->addReplyTo($replyTo);
            }
        }
    }

    /**
     * Internal logging
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            call_user_func($this->logger, $level, $message, $context);
        } else {
            $contextString = ! empty($context) ? ' | '.json_encode($context) : '';
            error_log("[MailService][$level] $message$contextString");
        }
    }
}
