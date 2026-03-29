<?php

declare(strict_types=1);

namespace App\Services\Mail;

/**
 * Mail Builder - API fluente pour construire et envoyer des emails
 *
 * ⚡ INDÉPENDANT DE LARAVEL - Fonctionne en PHP procédural
 *
 * Usage:
 * $mail->builder()
 *     ->to('user@example.com')
 *     ->cc('manager@example.com')
 *     ->subject('Test Email')
 *     ->body('<h1>Hello</h1>')
 *     ->attach('/path/to/file.pdf')
 *     ->send();
 */
class MailBuilder
{
    public function __construct(MailServiceInterface $mailService)
    {
        $this->mailService = $mailService;
    }

    private array $to = [];

    private array $cc = [];

    private array $bcc = [];

    private string $subject = '';

    private string $body = '';

    private array $attachments = [];

    private ?array $from = null;

    private ?array $replyTo = null;

    private bool $isQueued = false;

    private $logger = null;

    private MailServiceInterface $mailService;

    /**
     * Set recipient(s)
     *
     * @param  string|array  $email  Single email or array of emails
     * @param  string|null  $name  Optional name
     */
    public function to(string|array $email, ?string $name = null): self
    {
        if (is_array($email)) {
            foreach ($email as $addr) {
                $this->to[] = is_array($addr) ? $addr : ['email' => $addr, 'name' => null];
            }
        } else {
            $this->to[] = ['email' => $email, 'name' => $name];
        }

        return $this;
    }

    /**
     * Add CC recipient(s)
     *
     * @param  string|array  $email  Single email or array of emails
     * @param  string|null  $name  Optional name
     */
    public function cc(string|array $email, ?string $name = null): self
    {
        if (is_array($email)) {
            foreach ($email as $addr) {
                $this->cc[] = is_array($addr) ? $addr : ['email' => $addr, 'name' => null];
            }
        } else {
            $this->cc[] = ['email' => $email, 'name' => $name];
        }

        return $this;
    }

    /**
     * Add BCC recipient(s)
     *
     * @param  string|array  $email  Single email or array of emails
     * @param  string|null  $name  Optional name
     */
    public function bcc(string|array $email, ?string $name = null): self
    {
        if (is_array($email)) {
            foreach ($email as $addr) {
                $this->bcc[] = is_array($addr) ? $addr : ['email' => $addr, 'name' => null];
            }
        } else {
            $this->bcc[] = ['email' => $email, 'name' => $name];
        }

        return $this;
    }

    /**
     * Set email subject
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set email body (HTML)
     */
    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Set email body (alias for body)
     */
    public function html(string $html): self
    {
        return $this->body($html);
    }

    /**
     * Set plain text body
     */
    public function text(string $text): self
    {
        $this->body = nl2br(e($text));

        return $this;
    }

    /**
     * Add file attachment
     *
     * @param  string  $path  Path to file
     * @param  string|null  $name  Optional custom filename
     * @param  array  $options  Additional options (mime, as, etc.)
     */
    public function attach(string $path, ?string $name = null, array $options = []): self
    {
        $attachment = ['path' => $path];

        if ($name) {
            $attachment['options'] = array_merge($options, ['as' => $name]);
        } elseif (! empty($options)) {
            $attachment['options'] = $options;
        }

        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * Add multiple attachments
     *
     * @param  array  $files  Array of file paths or arrays with path/name
     */
    public function attachMany(array $files): self
    {
        foreach ($files as $file) {
            if (is_string($file)) {
                $this->attach($file);
            } elseif (is_array($file)) {
                $this->attach(
                    $file['path'],
                    $file['name'] ?? null,
                    $file['options'] ?? []
                );
            }
        }

        return $this;
    }

    /**
     * Attach file from storage
     *
     * @param  string  $path  Path relative to storage/app
     * @param  string|null  $name  Optional custom filename
     */
    public function attachFromStorage(string $path, ?string $name = null): self
    {
        $fullPath = storage_path('app/'.$path);

        return $this->attach($fullPath, $name);
    }

    /**
     * Attach data as file
     *
     * @param  string  $data  File content
     * @param  string  $name  Filename
     * @param  array  $options  Additional options
     */
    public function attachData(string $data, string $name, array $options = []): self
    {
        $this->attachments[] = [
            'type' => 'data',
            'data' => $data,
            'name' => $name,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * Set sender (from)
     * 
     * @param string|array $email Email address or array with 'email' and 'name'
     * @param string|null $name Optional name (only used if $email is string)
     */
    public function from(string|array $email, ?string $name = null): self
    {
        if (is_array($email)) {
            $this->from = $email;
        } else {
            $this->from = ['email' => $email, 'name' => $name];
        }

        return $this;
    }

    /**
     * Set reply-to address
     * 
     * @param string|array $email Email address or array with 'email' and 'name'
     * @param string|null $name Optional name (only used if $email is string)
     */
    public function replyTo(string|array $email, ?string $name = null): self
    {
        if (is_array($email)) {
            $this->replyTo = $email;
        } else {
            $this->replyTo = ['email' => $email, 'name' => $name];
        }

        return $this;
    }

    /**
     * Mark email to be queued instead of sent immediately
     */
    public function queued(): self
    {
        $this->isQueued = true;

        return $this;
    }

    /**
     * Set custom logger (optional)
     */
    public function setLogger(callable $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Send the email
     */
    public function send(): bool
    {
        if (empty($this->to)) {
            $this->log('error', 'Cannot send email: no recipients specified');

            return false;
        }

        if (empty($this->subject)) {
            $this->log('error', 'Cannot send email: no subject specified');

            return false;
        }

        try {
            // Build options array for MailService
            $options = [
                'cc' => $this->cc,
                'bcc' => $this->bcc,
                'attachments' => $this->attachments,
                'from' => $this->from,
                'replyTo' => $this->replyTo,
            ];

            // Use queue or send
            $method = $this->isQueued ? 'queue' : 'send';
            $result = $this->mailService->$method($this->to, $this->subject, $this->body, $options);

            if ($result) {
                $this->log('info', 'Email sent successfully', [
                    'to' => array_column($this->to, 'email'),
                    'subject' => $this->subject,
                    'queued' => $this->isQueued,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('error', 'Failed to send email', [
                'to' => array_column($this->to, 'email'),
                'subject' => $this->subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Internal logging method - works without Laravel
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            call_user_func($this->logger, $level, $message, $context);
        } else {
            // Fallback to native PHP error_log
            $contextString = ! empty($context) ? ' | '.json_encode($context) : '';
            error_log("[MailService][$level] $message$contextString");
        }
    }

    /**
     * Get current configuration (for debugging)
     */
    public function toArray(): array
    {
        return [
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'subject' => $this->subject,
            'body_length' => strlen($this->body),
            'attachments_count' => count($this->attachments),
            'from' => $this->from,
            'reply_to' => $this->replyTo,
            'queued' => $this->isQueued,
        ];
    }
}
