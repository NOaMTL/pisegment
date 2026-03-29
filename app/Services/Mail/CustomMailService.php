<?php

declare(strict_types=1);

namespace App\Services\Mail;

/**
 * Custom Mail Implementation - Utilise votre propre classe Mail
 *
 * Cette implémentation délègue à votre classe Mail personnalisée.
 * Adaptez-la selon l'API de votre classe Mail.
 *
 * Usage:
 * $mailer = new CustomMailService();
 * $mailer->send('user@example.com', 'Subject', '<h1>Body</h1>');
 * 
 * Ou avec le builder:
 * $mailer->builder()
 *     ->to('user@example.com')
 *     ->subject('Subject')
 *     ->html('<h1>Body</h1>')
 *     ->attach('/path/to/file.pdf')
 *     ->send();
 */
class CustomMailService implements MailServiceInterface
{
    private $logger = null;

    /**
     * Send a simple email using your Mail class
     *
     * @param  string|array  $to  Single recipient or array of recipients
     */
    public function send(string|array $to, string $subject, string $body, array $options = []): bool
    {
        try {
            // Normalize recipients to array
            $recipients = is_array($to) ? $to : [$to];

            // Extract options
            $cc = $options['cc'] ?? [];
            $bcc = $options['bcc'] ?? [];
            $attachments = $options['attachments'] ?? [];
            $from = $options['from'] ?? null;
            $replyTo = $options['replyTo'] ?? null;

            // Prepare data for your Mail class
            $mailData = [
                'to' => $recipients,
                'subject' => $subject,
                'body' => $body,
                'cc' => is_array($cc) ? $cc : [$cc],
                'bcc' => is_array($bcc) ? $bcc : [$bcc],
                'attachments' => $attachments,
            ];

            if ($from) {
                $mailData['from'] = $from;
            }

            if ($replyTo) {
                $mailData['replyTo'] = $replyTo;
            }

            // TODO: Adaptez cette ligne selon l'API de votre classe Mail
            // Exemple 1: Si votre Mail accepte un array de données
            // $result = \Mail::send($mailData);
            
            // Exemple 2: Si votre Mail a une API différente
            // $result = \Mail::send($to, $subject, $body, $cc, $bcc, $attachments);
            
            // Exemple 3: Si votre Mail utilise un builder
            // $mail = new \Mail();
            // $mail->to($to)->subject($subject)->body($body)->send();
            
            // Pour l'instant, on simule l'envoi
            // REMPLACEZ CETTE LIGNE PAR L'APPEL À VOTRE CLASSE MAIL
            $result = $this->callYourMailClass($mailData);

            if ($result) {
                $this->log('info', 'Email sent successfully via Mail class', [
                    'to' => $recipients,
                    'subject' => $subject,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('error', 'Failed to send email via Mail class', [
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
     * Send email to multiple recipients individually
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
        $this->log('warning', 'Queue not supported with Mail class, sending immediately');

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
     * Call your custom Mail class
     *
     * ⚠️ IMPORTANT: Adaptez cette méthode selon l'API de votre classe Mail
     *
     * @param  array  $mailData  Email data
     * @return bool Success
     */
    private function callYourMailClass(array $mailData): bool
    {
        // ============================================
        // TODO: REMPLACEZ CETTE SECTION PAR L'APPEL À VOTRE CLASSE MAIL
        // ============================================

        // Exemple 1: Si Mail::send() accepte tous les paramètres individuellement
        /*
        return \Mail::send(
            $mailData['to'],
            $mailData['subject'],
            $mailData['body'],
            $mailData['cc'] ?? [],
            $mailData['bcc'] ?? [],
            $mailData['attachments'] ?? [],
            $mailData['from'] ?? null,
            $mailData['replyTo'] ?? null
        );
        */

        // Exemple 2: Si Mail::send() accepte un array de configuration
        /*
        return \Mail::send($mailData);
        */

        // Exemple 3: Si Mail utilise un pattern builder
        /*
        $mail = new \Mail();
        
        foreach ($mailData['to'] as $recipient) {
            $mail->addTo($recipient);
        }
        
        $mail->setSubject($mailData['subject']);
        $mail->setBody($mailData['body']);
        
        if (!empty($mailData['cc'])) {
            foreach ($mailData['cc'] as $cc) {
                $mail->addCC($cc);
            }
        }
        
        if (!empty($mailData['bcc'])) {
            foreach ($mailData['bcc'] as $bcc) {
                $mail->addBCC($bcc);
            }
        }
        
        if (!empty($mailData['attachments'])) {
            foreach ($mailData['attachments'] as $attachment) {
                if (is_string($attachment)) {
                    $mail->addAttachment($attachment);
                } elseif (is_array($attachment) && isset($attachment['path'])) {
                    $mail->addAttachment($attachment['path']);
                }
            }
        }
        
        return $mail->send();
        */

        // Exemple 4: Si Mail::send() est statique et simple
        /*
        // Pour chaque destinataire
        foreach ($mailData['to'] as $recipient) {
            $success = \Mail::send(
                $recipient,
                $mailData['subject'],
                $mailData['body']
            );
            
            if (!$success) return false;
        }
        return true;
        */

        // ============================================
        // TEMPORAIRE: Pour l'instant on simule
        // ============================================
        $this->log('debug', 'Mail::send() called with data', $mailData);

        // Simuler l'envoi (REMPLACEZ PAR VOTRE CODE)
        return true;
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
