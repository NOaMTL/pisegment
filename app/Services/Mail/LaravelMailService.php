<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LaravelMailService implements MailServiceInterface
{
    /**
     * Send a simple email
     *
     * @param  string|array  $to  Single recipient or array of recipients
     */
    public function send(string|array $to, string $subject, string $body, array $options = []): bool
    {
        try {
            // Normalize recipients to array
            $recipients = is_array($to) ? $to : [$to];

            Mail::send([], [], function ($message) use ($recipients, $subject, $body, $options) {
                // Add all recipients
                foreach ($recipients as $recipient) {
                    if (is_array($recipient)) {
                        $message->to($recipient['email'], $recipient['name'] ?? null);
                    } else {
                        $message->to($recipient);
                    }
                }

                $message->subject($subject)
                    ->html($body);

                $this->applyOptions($message, $options);
            });

            Log::info('Email sent successfully', [
                'to' => $recipients,
                'subject' => $subject,
            ]);

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
     *
     * @param  string|array  $to  Single recipient or array of recipients
     */
    public function sendMailable(string|array $to, $mailable): bool
    {
        try {
            $recipients = is_array($to) ? $to : [$to];

            foreach ($recipients as $recipient) {
                Mail::to($recipient)->send($mailable);
            }

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
     *
     * @param  string|array  $to  Single recipient or array of recipients
     */
    public function queue(string|array $to, string $subject, string $body, array $options = []): bool
    {
        try {
            $recipients = is_array($to) ? $to : [$to];

            Mail::queue([], [], function ($message) use ($recipients, $subject, $body, $options) {
                // Add all recipients
                foreach ($recipients as $recipient) {
                    if (is_array($recipient)) {
                        $message->to($recipient['email'], $recipient['name'] ?? null);
                    } else {
                        $message->to($recipient);
                    }
                }

                $message->subject($subject)
                    ->html($body);

                $this->applyOptions($message, $options);
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

    /**
     * Create a mail builder for fluent configuration
     */
    public function builder(): MailBuilder
    {
        return new MailBuilder($this);
    }

    /**
     * Apply options to the message (CC, BCC, attachments, etc.)
     */
    private function applyOptions($message, array $options): void
    {
        // Handle CC
        if (isset($options['cc']) && ! empty($options['cc'])) {
            $cc = is_array($options['cc']) ? $options['cc'] : [$options['cc']];
            foreach ($cc as $ccRecipient) {
                if (is_array($ccRecipient)) {
                    $message->cc($ccRecipient['email'], $ccRecipient['name'] ?? null);
                } else {
                    $message->cc($ccRecipient);
                }
            }
        }

        // Handle BCC
        if (isset($options['bcc']) && ! empty($options['bcc'])) {
            $bcc = is_array($options['bcc']) ? $options['bcc'] : [$options['bcc']];
            foreach ($bcc as $bccRecipient) {
                if (is_array($bccRecipient)) {
                    $message->bcc($bccRecipient['email'], $bccRecipient['name'] ?? null);
                } else {
                    $message->bcc($bccRecipient);
                }
            }
        }

        // Handle attachments
        if (isset($options['attachments']) && is_array($options['attachments']) && ! empty($options['attachments'])) {
            foreach ($options['attachments'] as $attachment) {
                if (is_string($attachment)) {
                    // Simple file path
                    $message->attach($attachment);
                } elseif (is_array($attachment)) {
                    if (isset($attachment['type']) && $attachment['type'] === 'data') {
                        // Attach raw data
                        $message->attachData(
                            $attachment['data'],
                            $attachment['name'],
                            $attachment['options'] ?? []
                        );
                    } else {
                        // Attach file with options
                        $message->attach(
                            $attachment['path'],
                            $attachment['options'] ?? []
                        );
                    }
                }
            }
        }

        // Handle from address
        if (isset($options['from'])) {
            if (is_array($options['from'])) {
                $message->from(
                    $options['from']['email'],
                    $options['from']['name'] ?? null
                );
            } else {
                $message->from($options['from']);
            }
        }

        // Handle reply-to
        if (isset($options['replyTo'])) {
            if (is_array($options['replyTo'])) {
                $message->replyTo(
                    $options['replyTo']['email'],
                    $options['replyTo']['name'] ?? null
                );
            } else {
                $message->replyTo($options['replyTo']);
            }
        }
    }
}
