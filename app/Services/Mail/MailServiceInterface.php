<?php

namespace App\Services\Mail;

use Illuminate\Mail\Mailable;

interface MailServiceInterface
{
    /**
     * Send a simple email
     *
     * @param  string|array  $to  Recipient email address(es) - single string or array
     * @param  string  $subject  Email subject
     * @param  string  $body  Email body (HTML or plain text)
     * @param  array  $options  Additional options (cc, bcc, attachments, etc.)
     * @return bool Success status
     */
    public function send(string|array $to, string $subject, string $body, array $options = []): bool;

    /**
     * Send email using a Mailable class
     *
     * @param  string|array  $to  Recipient email address(es)
     * @param  Mailable  $mailable  Laravel Mailable instance
     * @return bool Success status
     */
    public function sendMailable(string|array $to, $mailable): bool;

    /**
     * Send email to multiple recipients
     *
     * @param  array  $recipients  Array of email addresses
     * @param  string  $subject  Email subject
     * @param  string  $body  Email body
     * @param  array  $options  Additional options
     * @return bool Success status
     */
    public function sendBulk(array $recipients, string $subject, string $body, array $options = []): bool;

    /**
     * Queue an email for later sending
     *
     * @param  string|array  $to  Recipient email address(es)
     * @param  string  $subject  Email subject
     * @param  string  $body  Email body
     * @param  array  $options  Additional options
     * @return bool Success status
     */
    public function queue(string|array $to, string $subject, string $body, array $options = []): bool;

    /**
     * Create a mail builder for fluent configuration
     *
     * @return MailBuilder
     */
    public function builder(): MailBuilder;
}
