<?php

namespace App\Services\Mail;

use Illuminate\Mail\Mailable;

interface MailServiceInterface
{
    /**
     * Send a simple email
     *
     * @param  string  $to  Recipient email address
     * @param  string  $subject  Email subject
     * @param  string  $body  Email body (HTML or plain text)
     * @param  array  $options  Additional options (cc, bcc, attachments, etc.)
     * @return bool Success status
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool;

    /**
     * Send email using a Mailable class
     *
     * @param  string  $to  Recipient email address
     * @param  Mailable  $mailable  Laravel Mailable instance
     * @return bool Success status
     */
    public function sendMailable(string $to, $mailable): bool;

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
     * @param  string  $to  Recipient email address
     * @param  string  $subject  Email subject
     * @param  string  $body  Email body
     * @param  array  $options  Additional options
     * @return bool Success status
     */
    public function queue(string $to, string $subject, string $body, array $options = []): bool;
}
