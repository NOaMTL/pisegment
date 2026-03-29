<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LaravelMailService implements MailServiceInterface
{
    /**
     * Send a simple email
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            Mail::send([], [], function ($message) use ($to, $subject, $body, $options) {
                $message->to($to)
                    ->subject($subject)
                    ->html($body);

                // Handle CC
                if (isset($options['cc'])) {
                    $message->cc($options['cc']);
                }

                // Handle BCC
                if (isset($options['bcc'])) {
                    $message->bcc($options['bcc']);
                }

                // Handle attachments
                if (isset($options['attachments']) && is_array($options['attachments'])) {
                    foreach ($options['attachments'] as $attachment) {
                        if (is_string($attachment)) {
                            $message->attach($attachment);
                        } elseif (is_array($attachment)) {
                            $message->attach(
                                $attachment['path'],
                                $attachment['options'] ?? []
                            );
                        }
                    }
                }

                // Handle from address
                if (isset($options['from'])) {
                    $message->from(
                        $options['from']['email'] ?? $options['from'],
                        $options['from']['name'] ?? null
                    );
                }

                // Handle reply-to
                if (isset($options['replyTo'])) {
                    $message->replyTo(
                        $options['replyTo']['email'] ?? $options['replyTo'],
                        $options['replyTo']['name'] ?? null
                    );
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send email using a Mailable class
     */
    public function sendMailable(string $to, $mailable): bool
    {
        try {
            Mail::to($to)->send($mailable);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send mailable', [
                'to' => $to,
                'mailable' => get_class($mailable),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send email to multiple recipients
     */
    public function sendBulk(array $recipients, string $subject, string $body, array $options = []): bool
    {
        try {
            foreach ($recipients as $recipient) {
                $this->send($recipient, $subject, $body, $options);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send bulk email', [
                'recipients_count' => count($recipients),
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Queue an email for later sending
     */
    public function queue(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            Mail::queue([], [], function ($message) use ($to, $subject, $body, $options) {
                $message->to($to)
                    ->subject($subject)
                    ->html($body);

                // Handle CC
                if (isset($options['cc'])) {
                    $message->cc($options['cc']);
                }

                // Handle BCC
                if (isset($options['bcc'])) {
                    $message->bcc($options['bcc']);
                }

                // Handle attachments
                if (isset($options['attachments']) && is_array($options['attachments'])) {
                    foreach ($options['attachments'] as $attachment) {
                        if (is_string($attachment)) {
                            $message->attach($attachment);
                        } elseif (is_array($attachment)) {
                            $message->attach(
                                $attachment['path'],
                                $attachment['options'] ?? []
                            );
                        }
                    }
                }

                // Handle from address
                if (isset($options['from'])) {
                    $message->from(
                        $options['from']['email'] ?? $options['from'],
                        $options['from']['name'] ?? null
                    );
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
