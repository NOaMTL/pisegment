# Mail Service - Complete Documentation

Service d'envoi d'emails avec **architecture interchangeable** permettant d'utiliser différentes implémentations (Laravel Mail, PHPMailer, SendGrid, etc.) via une interface commune.

## 📋 Table des Matières

- [Architecture](#-architecture)
- [Installation](#-installation)
- [Usage Laravel](#-usage-laravel)
- [Usage Standalone (PHP Procedural)](#-usage-standalone-php-procedural)
- [MailBuilder API](#-mailbuilder-api)
- [Attachments](#-attachments)
- [Multiple Recipients](#-multiple-recipients)
- [Interchangeable Implementations](#-interchangeable-implementations)
- [Examples](#-examples)

## 🏗 Architecture

```
MailServiceInterface
    ├── send(string|array $to, string $subject, string $body, array $options = []): bool
    ├── sendMailable(string|array $to, $mailable): bool
    ├── sendBulk(array $recipients, string $subject, string $body, array $options = []): bool
    ├── queue(string|array $to, string $subject, string $body, array $options = []): bool
    └── builder(): MailBuilder

Implementations:
    ├── LaravelMailService (uses Laravel Mail facade)
    ├── PHPMailerService (uses PHPMailer - standalone)
    ├── CustomMailService (adapter for your own Mail class) ⭐
    └── [Your Custom Implementation]

MailBuilder (Fluent API)
    ├── to(), cc(), bcc()
    ├── subject(), body(), html(), text()
    ├── attach(), attachMany(), attachFromStorage(), attachData()
    ├── from(), replyTo()
    ├── queued(), setLogger()
    └── send(), toArray()
```

## 📦 Installation

### Laravel (Default)

```bash
# Already included in Laravel
# Just use the interface
```

### Standalone (PHP Procedural)

```bash
# Install PHPMailer
composer require phpmailer/phpmailer

# Copy these files to your project:
# - app/Services/Mail/MailServiceInterface.php
# - app/Services/Mail/MailBuilder.php
# - app/Services/Mail/PHPMailerService.php
```

## 🚀 Usage Laravel

### Basic Setup

```php
use App\Services\Mail\MailServiceInterface;

class OrderController extends Controller
{
    public function __construct(
        private MailServiceInterface $mailService
    ) {}
    
    public function sendConfirmation(Order $order)
    {
        $this->mailService->send(
            to: $order->customer->email,
            subject: "Order #{$order->id} Confirmed",
            body: view('emails.order-confirmation', ['order' => $order])->render()
        );
    }
}
```

### Service Provider Binding

```php
// app/Providers/AppServiceProvider.php

use App\Services\Mail\MailServiceInterface;
use App\Services\Mail\LaravelMailService;

public function register(): void
{
    $this->app->singleton(MailServiceInterface::class, function ($app) {
        return new LaravelMailService();
    });
}
```

### Direct Methods

```php
use App\Services\Mail\MailServiceInterface;

// Simple email
app(MailServiceInterface::class)->send(
    to: 'user@example.com',
    subject: 'Welcome!',
    body: '<h1>Welcome to our app!</h1>'
);

// With options
app(MailServiceInterface::class)->send(
    to: 'client@example.com',
    subject: 'Invoice',
    body: view('emails.invoice')->render(),
    options: [
        'cc' => 'manager@company.com',
        'attachments' => [storage_path('invoices/invoice.pdf')],
    ]
);

// Queue email
app(MailServiceInterface::class)->queue(
    to: 'user@example.com',
    subject: 'Newsletter',
    body: view('emails.newsletter')->render()
);
```

### Fluent Builder

```php
use App\Services\Mail\MailServiceInterface;

$mailService = app(MailServiceInterface::class);

$mailService->builder()
    ->to('user@example.com')
    ->cc(['manager@example.com', 'supervisor@example.com'])
    ->subject('Project Update')
    ->html(view('emails.project-update')->render())
    ->attach(storage_path('reports/monthly.pdf'))
    ->queued()  // Send via queue
    ->send();
```

### With Dependency Injection

```php
use App\Services\Mail\MailServiceInterface;

class InvoiceService
{
    public function __construct(
        private MailServiceInterface $mailService
    ) {}
    
    public function sendInvoice(Invoice $invoice)
    {
        $this->mailService->builder()
            ->to($invoice->customer->email)
            ->cc($invoice->manager->email)
            ->subject("Invoice #{$invoice->number}")
            ->html(view('emails.invoice', ['invoice' => $invoice])->render())
            ->attach($invoice->pdfPath)
            ->send();
    }
}
```

## 🔧 Usage Standalone (PHP Procedural)

### Setup

```php
<?php

require 'vendor/autoload.php';

use App\Services\Mail\PHPMailerService;

$mailService = new PHPMailerService([
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your@gmail.com',
    'password' => 'app-password',
    'encryption' => 'tls',
    'from' => [
        'email' => 'noreply@myapp.com',
        'name' => 'My App'
    ],
]);
```

### Send Email

```php
$mailService->send(
    to: 'user@example.com',
    subject: 'Welcome',
    body: '<h1>Welcome!</h1>'
);
```

### See [STANDALONE_MAIL.md](./STANDALONE_MAIL.md) for full standalone documentation.

## 📝 MailBuilder API

### Recipients

```php
$builder->to('user@example.com');
$builder->to(['user1@example.com', 'user2@example.com']);
$builder->to([
    ['email' => 'john@example.com', 'name' => 'John Doe'],
    ['email' => 'jane@example.com', 'name' => 'Jane Smith'],
]);

$builder->cc('manager@example.com');
$builder->cc(['manager1@example.com', 'manager2@example.com']);

$builder->bcc('archive@example.com');
$builder->bcc(['archive1@example.com', 'archive2@example.com']);
```

### Content

```php
// Subject
$builder->subject('Order Confirmation');

// HTML body
$builder->html('<h1>Order Confirmed</h1><p>Thank you!</p>');

// Plain text body
$builder->text('Order Confirmed. Thank you!');

// Or use body() for HTML
$builder->body('<h1>Hello</h1>');
```

### Attachments

```php
// Single file
$builder->attach('/path/to/file.pdf');

// Multiple files
$builder->attachMany([
    '/path/to/file1.pdf',
    '/path/to/file2.xlsx',
]);

// With custom filename
$builder->attach('/path/to/file.pdf', ['as' => 'Invoice-2024.pdf']);

// From Laravel storage
$builder->attachFromStorage('invoices/invoice.pdf');

// From string data (generated content)
$builder->attachData($pdfContent, 'report.pdf', ['mime' => 'application/pdf']);
```

### From & Reply-To

```php
$builder->from('sender@example.com');
$builder->from(['email' => 'sender@example.com', 'name' => 'Sender Name']);

$builder->replyTo('support@example.com');
$builder->replyTo(['email' => 'support@example.com', 'name' => 'Support Team']);
```

### Queueing (Laravel only)

```php
$builder->queued()->send();  // Send via queue
$builder->queued(false)->send();  // Send immediately
```

### Logging

```php
$builder->setLogger(function($level, $message, $context) {
    error_log("[$level] $message");
});
```

### Debugging

```php
// Get email data without sending
$data = $builder->toArray();
print_r($data);
```

### Complete Example

```php
$mailService->builder()
    ->to([
        ['email' => 'john@example.com', 'name' => 'John Doe'],
        ['email' => 'jane@example.com', 'name' => 'Jane Smith'],
    ])
    ->cc('manager@example.com')
    ->bcc(['archive@example.com', 'compliance@example.com'])
    ->subject('Monthly Report')
    ->html('<h1>Report</h1><p>See attachment.</p>')
    ->attachMany([
        '/path/to/report.pdf',
        '/path/to/data.xlsx',
    ])
    ->from(['email' => 'reports@company.com', 'name' => 'Reports System'])
    ->replyTo('support@company.com')
    ->queued()  // Laravel only
    ->send();
```

## 📎 Attachments

### Simple File Path

```php
$mailService->send(
    to: 'user@example.com',
    subject: 'Documents',
    body: '<p>Files attached.</p>',
    options: [
        'attachments' => [
            '/path/to/file1.pdf',
            '/path/to/file2.xlsx',
        ]
    ]
);
```

### With Custom Filename

```php
$mailService->send(
    to: 'user@example.com',
    subject: 'Invoice',
    body: '<p>Invoice attached.</p>',
    options: [
        'attachments' => [
            [
                'path' => '/var/invoices/12345.pdf',
                'options' => ['as' => 'Invoice-2024-001.pdf']
            ]
        ]
    ]
);
```

### From String Data (Generated Content)

```php
$pdfContent = generatePDF();  // Your PDF generation logic

$mailService->send(
    to: 'user@example.com',
    subject: 'Generated Report',
    body: '<p>Your report is attached.</p>',
    options: [
        'attachments' => [
            [
                'type' => 'data',
                'data' => $pdfContent,
                'name' => 'report.pdf',
                'options' => ['mime' => 'application/pdf']
            ]
        ]
    ]
);
```

### From Laravel Storage

```php
// With LaravelMailService only
$mailService->builder()
    ->to('user@example.com')
    ->subject('Files from Storage')
    ->html('<p>Files attached from storage.</p>')
    ->attachFromStorage('documents/contract.pdf')
    ->attachFromStorage('documents/terms.pdf', ['as' => 'Terms-and-Conditions.pdf'])
    ->send();
```

### Using Builder

```php
$mailService->builder()
    ->to('user@example.com')
    ->subject('Multiple Attachments')
    ->html('<p>Multiple files attached.</p>')
    ->attach('/path/to/file1.pdf')
    ->attach('/path/to/file2.pdf', ['as' => 'Custom-Name.pdf'])
    ->attachMany([
        '/path/to/file3.xlsx',
        '/path/to/file4.docx',
    ])
    ->attachData($generatedPDF, 'report.pdf')
    ->send();
```

## 👥 Multiple Recipients

### Array of Emails

```php
$mailService->send(
    to: [
        'user1@example.com',
        'user2@example.com',
        'user3@example.com',
    ],
    subject: 'Team Announcement',
    body: '<p>Important update...</p>'
);
```

### Array with Names

```php
$mailService->send(
    to: [
        ['email' => 'john@example.com', 'name' => 'John Doe'],
        ['email' => 'jane@example.com', 'name' => 'Jane Smith'],
    ],
    subject: 'Personal Invitation',
    body: '<p>You are invited...</p>'
);
```

### CC and BCC

```php
$mailService->send(
    to: 'client@company.com',
    subject: 'Invoice',
    body: '<p>Invoice attached.</p>',
    options: [
        'cc' => ['manager@company.com', 'supervisor@company.com'],
        'bcc' => ['archive@company.com', 'compliance@company.com'],
    ]
);
```

### Bulk Send (Individual Emails)

```php
// Each recipient gets their own email (not visible to others)
$mailService->sendBulk(
    recipients: [
        'user1@example.com',
        'user2@example.com',
        'user3@example.com',
    ],
    subject: 'Personal Newsletter',
    body: '<p>This email was sent to you.</p>'
);
```

## 🔄 Interchangeable Implementations

L'architecture interface-based permet de **changer d'implémentation sans toucher à votre code**.

### Laravel Implementation

```php
use App\Services\Mail\LaravelMailService;
use App\Services\Mail\MailServiceInterface;

// In AppServiceProvider
$this->app->singleton(MailServiceInterface::class, LaravelMailService::class);

// Usage (same API)
app(MailServiceInterface::class)->send(...);
```

### PHPMailer Implementation (Standalone)

```php
use App\Services\Mail\PHPMailerService;

$mailService = new PHPMailerService([
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your@gmail.com',
    'password' => 'app-password',
    'encryption' => 'tls',
]);

// Usage (same API)
$mailService->send(...);
```

### Create Custom Implementation

**💡 Tip:** Si vous avez **votre propre classe `Mail`**, utilisez `CustomMailService` comme template ! Voir [CUSTOM_MAIL_GUIDE.md](./CUSTOM_MAIL_GUIDE.md)

**Exemple - Créer un service SendGrid :**

```php
use App\Services\Mail\MailServiceInterface;
use App\Services\Mail\MailBuilder;

class SendGridService implements MailServiceInterface
{
    private string $apiKey;
    
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }
    
    public function send(string|array $to, string $subject, string $body, array $options = []): bool
    {
        $client = new \SendGrid\Mail\Mail();
        $client->setFrom($options['from'] ?? 'noreply@example.com');
        
        $recipients = is_array($to) ? $to : [$to];
        foreach ($recipients as $recipient) {
            $client->addTo($recipient);
        }
        
        $client->setSubject($subject);
        $client->addContent("text/html", $body);
        
        // Handle attachments
        if (isset($options['attachments'])) {
            foreach ($options['attachments'] as $attachment) {
                // Your attachment logic
            }
        }
        
        $sendgrid = new \SendGrid($this->apiKey);
        $response = $sendgrid->send($client);
        
        return $response->statusCode() === 202;
    }
    
    public function sendMailable(string|array $to, $mailable): bool
    {
        throw new \Exception('Mailables not supported with SendGrid');
    }
    
    public function sendBulk(array $recipients, string $subject, string $body, array $options = []): bool
    {
        $success = true;
        foreach ($recipients as $recipient) {
            if (!$this->send($recipient, $subject, $body, $options)) {
                $success = false;
            }
        }
        return $success;
    }
    
    public function queue(string|array $to, string $subject, string $body, array $options = []): bool
    {
        // Fallback to immediate send
        return $this->send($to, $subject, $body, $options);
    }
    
    public function builder(): MailBuilder
    {
        return new MailBuilder($this);
    }
}

// Usage (same API!)
$mailService = new SendGridService('your-api-key');
$mailService->send(...);
```

### Swap at Runtime

```php
// Production: Use Laravel Mail
if (app()->environment('production')) {
    $mailService = new LaravelMailService();
}
// Development: Use Mailtrap or local SMTP
else {
    $mailService = new PHPMailerService([
        'host' => 'smtp.mailtrap.io',
        'port' => 2525,
        'username' => 'mailtrap-user',
        'password' => 'mailtrap-pass',
    ]);
}

// Usage stays the same
$mailService->send(...);
```

## 📚 Examples

### Order Confirmation

```php
public function sendOrderConfirmation(Order $order): bool
{
    return app(MailServiceInterface::class)->builder()
        ->to($order->customer->email)
        ->subject("Order #{$order->number} Confirmed")
        ->html(view('emails.order-confirmation', ['order' => $order])->render())
        ->attach($order->invoicePath)
        ->from(['email' => 'orders@shop.com', 'name' => 'Our Shop'])
        ->queued()
        ->send();
}
```

### Password Reset

```php
public function sendPasswordReset(User $user, string $token): bool
{
    $resetUrl = url("/reset-password?token=$token");
    
    return $this->mailService->send(
        to: $user->email,
        subject: 'Password Reset Request',
        body: view('emails.password-reset', [
            'user' => $user,
            'resetUrl' => $resetUrl,
        ])->render()
    );
}
```

### Weekly Newsletter

```php
public function sendNewsletter(Collection $subscribers): bool
{
    $recipients = $subscribers->pluck('email')->toArray();
    
    return $this->mailService->sendBulk(
        recipients: $recipients,
        subject: 'Weekly Newsletter - ' . now()->format('F j, Y'),
        body: view('emails.newsletter')->render(),
        options: [
            'from' => ['email' => 'newsletter@company.com', 'name' => 'Newsletter']
        ]
    );
}
```

### Invoice with PDF

```php
public function sendInvoice(Invoice $invoice): bool
{
    // Generate PDF
    $pdf = \PDF::loadView('invoices.pdf', ['invoice' => $invoice]);
    $pdfContent = $pdf->output();
    
    return $this->mailService->builder()
        ->to($invoice->client->email)
        ->cc($invoice->manager->email)
        ->subject("Invoice #{$invoice->number}")
        ->html(view('emails.invoice', ['invoice' => $invoice])->render())
        ->attachData($pdfContent, "invoice-{$invoice->number}.pdf", [
            'mime' => 'application/pdf'
        ])
        ->send();
}
```

### Team Announcement

```php
public function announceToTeam(string $title, string $message): bool
{
    $team = User::where('role', 'team_member')->get();
    
    $recipients = $team->map(fn($user) => [
        'email' => $user->email,
        'name' => $user->name,
    ])->toArray();
    
    return $this->mailService->send(
        to: $recipients,
        subject: $title,
        body: view('emails.announcement', [
            'title' => $title,
            'message' => $message,
        ])->render(),
        options: [
            'from' => ['email' => 'team@company.com', 'name' => 'Team Updates']
        ]
    );
}
```

## 🔑 Key Benefits

✅ **Interchangeable**: Swap implementations without touching code  
✅ **Portable**: PHPMailerService works in pure PHP (no Laravel)  
✅ **Consistent API**: Same methods across all implementations  
✅ **Fluent Builder**: Clean, readable email construction  
✅ **Multiple Recipients**: Array support with names  
✅ **Attachments**: Files, storage, or generated content  
✅ **CC/BCC**: Full support for carbon copies  
✅ **Queuing**: Laravel Mail queue support (automatic fallback for standalone)  
✅ **Logging**: Optional custom logger with error_log() fallback  
✅ **Type-Safe**: PHP 8+ union types (`string|array`)  
✅ **Testable**: Easy to mock with interface  

## 📖 See Also

- [STANDALONE_MAIL.md](./STANDALONE_MAIL.md) - Full standalone (PHP procedural) guide
- [example-standalone.php](./example-standalone.php) - 13 complete examples
- [HTTP Client Service](../HttpClient/README.md) - Similar interchangeable architecture

---

**Summary:** Le Mail Service suit le même pattern que l'HTTP Client - **100% interchangeable** via une interface, utilisable dans Laravel OU PHP procédural. Même API, différentes implémentations ! 🚀
