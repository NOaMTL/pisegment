# Mail Service - Quick Start Guide

Guide de démarrage rapide pour le Mail Service avec architecture interchangeable.

## 🎯 Concepts Clés

1. **Interface-based**: `MailServiceInterface` définit le contrat
2. **Interchangeable**: Plusieurs implémentations possibles (Laravel, PHPMailer, Custom Mail, SendGrid...)
3. **Portable**: Fonctionne dans Laravel OU PHP procédural
4. **Fluent API**: `MailBuilder` pour construire des emails élégamment

## 🔄 Implémentations Disponibles

- **LaravelMailService** - Utilise Laravel Mail facade (pour Laravel)
- **PHPMailerService** - Utilise PHPMailer SMTP (standalone)
- **CustomMailService** - Adaptateur pour votre propre classe Mail ⭐

## ⚡ Quick Start - Laravel

### 1. Service Provider (Optional)

```php
// app/Providers/AppServiceProvider.php

use App\Services\Mail\MailServiceInterface;
use App\Services\Mail\LaravelMailService;

public function register(): void
{
    $this->app->singleton(MailServiceInterface::class, LaravelMailService::class);
}
```

### 2. Simple Usage

```php
use App\Services\Mail\MailServiceInterface;

// Via dependency injection
public function __construct(
    private MailServiceInterface $mailService
) {}

// Send email
$this->mailService->send(
    to: 'user@example.com',
    subject: 'Welcome!',
    body: '<h1>Welcome to our app!</h1>'
);
```

### 3. Fluent Builder

```php
$this->mailService->builder()
    ->to('user@example.com')
    ->subject('Order Confirmed')
    ->html('<h1>Thank you!</h1>')
    ->attach(storage_path('invoice.pdf'))
    ->send();
```

### 4. Multiple Recipients

```php
$this->mailService->send(
    to: ['user1@example.com', 'user2@example.com', 'user3@example.com'],
    subject: 'Team Update',
    body: '<p>Important announcement...</p>'
);
```

### 5. With Attachments

```php
$this->mailService->send(
    to: 'client@example.com',
    subject: 'Invoice',
    body: '<p>Invoice attached.</p>',
    options: [
        'attachments' => [storage_path('invoices/invoice.pdf')]
    ]
);
```

### 6. Queue Email

```php
$this->mailService->queue(
    to: 'user@example.com',
    subject: 'Newsletter',
    body: view('emails.newsletter')->render()
);
```

## ⚡ Quick Start - PHP Procedural

### 1. Installation

```bash
composer require phpmailer/phpmailer
```

### 2. Copy Files

Copy these 3 files to your PHP project:
- `app/Services/Mail/MailServiceInterface.php`
- `app/Services/Mail/MailBuilder.php`
- `app/Services/Mail/PHPMailerService.php`

### 3. Setup

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

### 4. Send Email

```php
$mailService->send(
    to: 'user@example.com',
    subject: 'Welcome',
    body: '<h1>Welcome!</h1>'
);
```

### 5. Fluent Builder

```php
$mailService->builder()
    ->to('user@example.com')
    ->subject('Order Confirmed')
    ->html('<h1>Thank you!</h1>')
    ->attach('/path/to/invoice.pdf')
    ->send();
```

### Option Alternative : Votre Propre Classe Mail ⭐

Si vous utilisez **votre propre classe `Mail::send(...)`**, utilisez `CustomMailService` :

**1. Copiez ces 3 fichiers :**
- `MailServiceInterface.php`
- `MailBuilder.php`  
- `CustomMailService.php`

**2. Adaptez la méthode `callYourMailClass()`** dans `CustomMailService.php` selon votre API Mail

**3. Utilisez-le (même API) :**
```php
$mailService = new CustomMailService();

$mailService->send('user@example.com', 'Subject', '<h1>Body</h1>');

$mailService->builder()
    ->to('user@example.com')
    ->subject('Test')
    ->attach('/path/to/file.pdf')
    ->send();
```

**📚 Guide complet : [CUSTOM_MAIL_GUIDE.md](./CUSTOM_MAIL_GUIDE.md)**

## 📋 Common Use Cases

### Send Order Confirmation

```php
// Laravel
public function sendOrderConfirmation(Order $order)
{
    $this->mailService->builder()
        ->to($order->customer->email)
        ->subject("Order #{$order->id} Confirmed")
        ->html(view('emails.order', ['order' => $order])->render())
        ->attach($order->invoicePath)
        ->queued()
        ->send();
}

// PHP Procedural
$mailService->builder()
    ->to($order['customer_email'])
    ->subject("Order #{$order['id']} Confirmed")
    ->html("<h1>Order Confirmed!</h1><p>Order: {$order['id']}</p>")
    ->attach($order['invoice_path'])
    ->send();
```

### Send with Multiple Attachments

```php
$mailService->builder()
    ->to('client@example.com')
    ->subject('Project Files')
    ->html('<p>All files attached.</p>')
    ->attachMany([
        '/path/to/file1.pdf',
        '/path/to/file2.xlsx',
        '/path/to/file3.docx',
    ])
    ->send();
```

### Send to Multiple Recipients

```php
$mailService->send(
    to: [
        ['email' => 'john@example.com', 'name' => 'John Doe'],
        ['email' => 'jane@example.com', 'name' => 'Jane Smith'],
    ],
    subject: 'Team Meeting',
    body: '<p>Meeting tomorrow at 10 AM.</p>'
);
```

### Send Bulk Emails (Individual)

```php
$mailService->sendBulk(
    recipients: ['user1@example.com', 'user2@example.com', 'user3@example.com'],
    subject: 'Newsletter',
    body: '<p>Monthly newsletter content...</p>'
);
```

### Generated PDF Attachment

```php
// Generate PDF
$pdfContent = generateInvoicePDF($invoice);

// Send with PDF attached
$mailService->send(
    to: 'client@example.com',
    subject: 'Invoice',
    body: '<p>Invoice attached.</p>',
    options: [
        'attachments' => [
            [
                'type' => 'data',
                'data' => $pdfContent,
                'name' => 'invoice.pdf',
                'options' => ['mime' => 'application/pdf']
            ]
        ]
    ]
);
```

## 🔄 Switching Implementations

### Laravel to PHPMailer

```php
// Before (Laravel)
$mailService = app(MailServiceInterface::class);  // LaravelMailService

// After (PHPMailer)
$mailService = new PHPMailerService([...config...]);

// Same API - no code changes!
$mailService->send(...);
```

### Laravel to Custom Mail

```php
// Before (Laravel)
$mailService = new LaravelMailService();

// After (Your Mail class)
$mailService = new CustomMailService();

// Same API!
$mailService->send(...);
```

### Runtime Decision

```php
// Choose implementation based on environment
if (app()->environment('production')) {
    $mailService = new LaravelMailService();
} elseif (defined('USE_CUSTOM_MAIL')) {
    $mailService = new CustomMailService();
} else {
    $mailService = new PHPMailerService([
        'host' => 'smtp.mailtrap.io',
        'port' => 2525,
        'username' => env('MAILTRAP_USERNAME'),
        'password' => env('MAILTRAP_PASSWORD'),
    ]);
}
```

## 📝 MailBuilder API Cheat Sheet

```php
// Recipients
->to(string|array $to)
->cc(string|array $cc)
->bcc(string|array $bcc)

// Content
->subject(string $subject)
->html(string $html)  // or ->body($html)
->text(string $text)

// Attachments
->attach(string $path, array $options = [])
->attachMany(array $paths)
->attachFromStorage(string $path, array $options = [])  // Laravel only
->attachData(string $data, string $name, array $options = [])

// Sender
->from(string|array $from)
->replyTo(string|array $replyTo)

// Options
->queued(bool $queued = true)  // Laravel only
->setLogger(callable $logger)

// Execution
->send(): bool
->toArray(): array  // Debug
```

## 🎯 MailService Methods

```php
// Direct send
send(string|array $to, string $subject, string $body, array $options = []): bool

// Send mailable (Laravel only)
sendMailable(string|array $to, $mailable): bool

// Bulk send (individual emails)
sendBulk(array $recipients, string $subject, string $body, array $options = []): bool

// Queue (Laravel only, fallback to send for PHPMailer)
queue(string|array $to, string $subject, string $body, array $options = []): bool

// Get builder
builder(): MailBuilder
```

## 🔑 Options Array

```php
$options = [
    'cc' => 'manager@example.com',  // string or array
    'bcc' => ['archive@example.com', 'compliance@example.com'],  // string or array
    'attachments' => [
        '/path/to/file.pdf',  // Simple
        ['path' => '/path/to/file.pdf', 'options' => ['as' => 'Custom.pdf']],  // With name
        ['type' => 'data', 'data' => $content, 'name' => 'file.pdf'],  // From data
    ],
    'from' => ['email' => 'sender@example.com', 'name' => 'Sender'],  // Override default
    'replyTo' => 'support@example.com',  // string or array
];
```

## 🛠 Configuration - PHPMailer

```php
$config = [
    'host' => 'smtp.gmail.com',          // SMTP host
    'port' => 587,                       // SMTP port (587 for TLS, 465 for SSL)
    'username' => 'your@gmail.com',      // SMTP username
    'password' => 'app-password',        // SMTP password
    'encryption' => 'tls',               // tls, ssl, or null
    'from' => [
        'email' => 'noreply@myapp.com',
        'name' => 'My Application'
    ],
    'debug' => false,                    // Enable SMTP debug output
];

$mailService = new PHPMailerService($config);
```

## 📚 Documentation

- [**README_MAIL.md**](./README_MAIL.md) - Complete documentation
- [**STANDALONE_MAIL.md**](./STANDALONE_MAIL.md) - PHP procedural full guide
- [**example-standalone.php**](./example-standalone.php) - 13 complete examples

## 🎯 Key Benefits

✅ **Même API partout** - Laravel ou PHP procédural  
✅ **Interchangeable** - Swap implementations facilement  
✅ **Multiple recipients** - Array support avec noms  
✅ **Attachments flexibles** - Files, storage, ou generated data  
✅ **Fluent builder** - Code lisible et maintenable  
✅ **Type-safe** - PHP 8+ union types  

---

**Ready to go!** Choisissez `LaravelMailService` pour Laravel ou `PHPMailerService` pour PHP procédural - même API, différentes implémentations ! 🚀
