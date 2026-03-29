# Mail Service - Standalone Usage (PHP Procedural)

Ce guide explique comment utiliser le Mail Service **en dehors de Laravel**, dans un projet PHP procédural.

## 📦 Dépendances

```bash
composer require phpmailer/phpmailer
```

## 🎯 Architecture Interchangeable

Le mail service utilise une interface qui permet de **changer d'implémentation facilement** :

```
MailServiceInterface (contract)
    ├── PHPMailerService (standalone - PHPMailer)
    ├── LaravelMailService (Laravel - Mail facade)
    └── SwiftMailerService (alternative - SwiftMailer)
```

## 🚀 Basic Usage (PHP Procedural)

### Setup - Copier les fichiers nécessaires

Pour utiliser dans un projet PHP procédural, **copiez ces 3 fichiers** :

```
app/Services/Mail/
    ├── MailServiceInterface.php      (interface)
    ├── MailBuilder.php                (builder fluent)
    └── PHPMailerService.php           (implémentation standalone)
```

### 1. Configuration

```php
<?php

require 'vendor/autoload.php';

use App\Services\Mail\PHPMailerService;
use App\Services\Mail\MailBuilder;

// Create service with SMTP config
$mailService = new PHPMailerService([
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your@gmail.com',
    'password' => 'your-app-password',
    'encryption' => 'tls',  // tls, ssl, or null
    'from' => [
        'email' => 'noreply@myapp.com',
        'name' => 'My Application'
    ],
    'debug' => false,  // true for SMTP debug output
]);

// Optional: Add custom logger
$mailService->setLogger(function($level, $message, $context) {
    error_log("[$level] $message " . json_encode($context));
});
```

### 2. Simple Email

```php
// Direct method
$mailService->send(
    to: 'user@example.com',
    subject: 'Welcome!',
    body: '<h1>Hello</h1><p>Welcome to our app!</p>'
);
```

### 3. Multiple Recipients

```php
// Array of emails
$mailService->send(
    to: ['user1@example.com', 'user2@example.com', 'user3@example.com'],
    subject: 'Team Update',
    body: '<p>Important team announcement...</p>'
);

// Array with names
$mailService->send(
    to: [
        ['email' => 'john@example.com', 'name' => 'John Doe'],
        ['email' => 'jane@example.com', 'name' => 'Jane Smith'],
    ],
    subject: 'Personal Invite',
    body: '<p>You are invited...</p>'
);
```

### 4. With CC and BCC

```php
$mailService->send(
    to: 'client@example.com',
    subject: 'Invoice #1234',
    body: '<p>Please find your invoice attached.</p>',
    options: [
        'cc' => 'manager@example.com',
        'bcc' => ['accounting@example.com', 'archive@example.com'],
    ]
);
```

### 5. With Attachments

```php
$mailService->send(
    to: 'user@example.com',
    subject: 'Documents',
    body: '<p>Please review the attached documents.</p>',
    options: [
        'attachments' => [
            '/var/www/files/invoice.pdf',
            '/var/www/files/report.xlsx',
        ]
    ]
);

// With custom filename
$mailService->send(
    to: 'user@example.com',
    subject: 'Report',
    body: '<p>Monthly report attached.</p>',
    options: [
        'attachments' => [
            [
                'path' => '/path/to/file.pdf',
                'options' => ['as' => 'Custom-Filename.pdf']
            ]
        ]
    ]
);

// Attach from string data (PDF generated in memory)
$pdfContent = generatePDF(); // your PDF generation logic

$mailService->send(
    to: 'user@example.com',
    subject: 'Generated Report',
    body: '<p>Your custom report is attached.</p>',
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

### 6. Fluent API (MailBuilder)

```php
// Create builder
$builder = $mailService->builder();

// Build email with fluent API
$builder
    ->to('user@example.com')
    ->cc(['manager@example.com', 'supervisor@example.com'])
    ->bcc('archive@example.com')
    ->subject('Monthly Report')
    ->html('<h1>Report</h1><p>See attachment.</p>')
    ->attach('/path/to/report.pdf')
    ->attach('/path/to/data.xlsx')
    ->from(['email' => 'reports@myapp.com', 'name' => 'Reports System'])
    ->replyTo('support@myapp.com')
    ->send();
```

### 7. Multiple Attachments (Fluent)

```php
$mailService->builder()
    ->to('client@example.com')
    ->subject('Project Files')
    ->html('<p>All project files attached.</p>')
    ->attachMany([
        '/path/to/file1.pdf',
        '/path/to/file2.docx',
        '/path/to/file3.xlsx',
    ])
    ->send();
```

### 8. Send to Multiple Recipients Individually (Bulk)

```php
// Each recipient gets their own email (not visible to others)
$recipients = [
    'user1@example.com',
    'user2@example.com',
    'user3@example.com',
];

$mailService->sendBulk(
    recipients: $recipients,
    subject: 'Personal Message',
    body: '<p>This message was sent just to you.</p>'
);
```

## 🔄 Swapping Implementations

**C'est ici que l'architecture brille** : vous pouvez changer d'implémentation sans toucher à votre code !

### Example: Switch from PHPMailer to Laravel

```php
// In PHP procedural
$mailService = new PHPMailerService([...]);

// In Laravel
$mailService = app(MailServiceInterface::class); // resolves to LaravelMailService

// Same API for both!
$mailService->send('user@example.com', 'Subject', 'Body');
```

### Example: Create Custom Implementation

```php
class SendGridService implements MailServiceInterface 
{
    public function send(string|array $to, string $subject, string $body, array $options = []): bool
    {
        // Your SendGrid API implementation
        $client = new SendGrid\Mail\Mail();
        $client->setFrom($this->from);
        $client->addTo($to);
        $client->setSubject($subject);
        $client->addContent("text/html", $body);
        
        $sendgrid = new SendGrid($this->apiKey);
        $response = $sendgrid->send($client);
        
        return $response->statusCode() === 202;
    }
    
    // ... implement other methods
}

// Use it the same way
$mailService = new SendGridService(['api_key' => 'xxx']);
$mailService->send(...);
```

## 📝 Complete Procedural Example

```php
<?php

require 'vendor/autoload.php';

use App\Services\Mail\PHPMailerService;

// ============================================
// 1. CONFIGURE MAIL SERVICE
// ============================================
$mailer = new PHPMailerService([
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'myapp@gmail.com',
    'password' => 'app-password',
    'encryption' => 'tls',
    'from' => [
        'email' => 'noreply@myapp.com',
        'name' => 'My Application'
    ],
]);

// ============================================
// 2. SEND WELCOME EMAIL WITH ATTACHMENT
// ============================================
$newUser = [
    'email' => 'newuser@example.com',
    'name' => 'John Doe'
];

$mailer->builder()
    ->to([$newUser['email'] => $newUser['name']])
    ->subject('Welcome to My Application!')
    ->html("
        <h1>Welcome {$newUser['name']}!</h1>
        <p>Thank you for joining us.</p>
        <p>Please find our user guide attached.</p>
    ")
    ->attach('/path/to/user-guide.pdf')
    ->send();

// ============================================
// 3. SEND INVOICE TO CLIENT WITH CC TO MANAGER
// ============================================
$invoice = [
    'number' => 'INV-2024-001',
    'client_email' => 'client@company.com',
    'manager_email' => 'manager@mycompany.com',
    'pdf_path' => '/var/invoices/INV-2024-001.pdf',
];

$mailer->send(
    to: $invoice['client_email'],
    subject: "Invoice {$invoice['number']}",
    body: "<p>Please find invoice {$invoice['number']} attached.</p>",
    options: [
        'cc' => $invoice['manager_email'],
        'attachments' => [$invoice['pdf_path']],
        'replyTo' => 'billing@mycompany.com',
    ]
);

// ============================================
// 4. SEND MONTHLY REPORT TO TEAM
// ============================================
$team = [
    'alice@company.com',
    'bob@company.com',
    'charlie@company.com',
];

$reportPDF = generateMonthlyReport(); // Returns PDF binary

$mailer->send(
    to: $team,
    subject: 'Monthly Report - ' . date('F Y'),
    body: '<h2>Monthly Report</h2><p>See attached PDF for details.</p>',
    options: [
        'attachments' => [
            [
                'type' => 'data',
                'data' => $reportPDF,
                'name' => 'monthly-report-' . date('Y-m') . '.pdf',
                'options' => ['mime' => 'application/pdf']
            ]
        ]
    ]
);

echo "All emails sent successfully!\n";
```

## 🔧 Configuration Options

### SMTP Settings

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `host` | string | `localhost` | SMTP server hostname |
| `port` | int | `587` | SMTP port (587 for TLS, 465 for SSL) |
| `username` | string | `''` | SMTP username (email) |
| `password` | string | `''` | SMTP password |
| `encryption` | string\|null | `tls` | Encryption: `tls`, `ssl`, or `null` |
| `from` | array | `['email' => '...']` | Default sender |
| `debug` | bool | `false` | Enable SMTP debug output |

### Gmail Example

```php
$config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your@gmail.com',
    'password' => 'app-password',  // Use App Password, not your Gmail password
    'encryption' => 'tls',
];
```

### Office 365 Example

```php
$config = [
    'host' => 'smtp.office365.com',
    'port' => 587,
    'username' => 'your@company.com',
    'password' => 'your-password',
    'encryption' => 'tls',
];
```

### Mailgun Example

```php
$config = [
    'host' => 'smtp.mailgun.org',
    'port' => 587,
    'username' => 'postmaster@yourdomain.mailgun.org',
    'password' => 'your-mailgun-password',
    'encryption' => 'tls',
];
```

## 🎯 Use Cases

### 1. Cron Job - Daily Reports

```php
#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use App\Services\Mail\PHPMailerService;

$mailer = new PHPMailerService([...]);

// Generate report
$reportData = generateDailyReport();

// Send to managers
$mailer->send(
    to: ['manager1@company.com', 'manager2@company.com'],
    subject: 'Daily Report - ' . date('Y-m-d'),
    body: "<h1>Daily Report</h1><pre>$reportData</pre>"
);
```

### 2. Order Confirmation (E-commerce)

```php
function sendOrderConfirmation($order) {
    global $mailer;
    
    $mailer->builder()
        ->to($order['customer_email'])
        ->subject("Order Confirmation - #{$order['id']}")
        ->html(renderOrderEmail($order))
        ->attach($order['invoice_pdf_path'])
        ->send();
}
```

### 3. Password Reset

```php
function sendPasswordReset($user, $token) {
    global $mailer;
    
    $resetLink = "https://myapp.com/reset?token=$token";
    
    $mailer->send(
        to: $user['email'],
        subject: 'Password Reset Request',
        body: "
            <p>Hello {$user['name']},</p>
            <p>Click here to reset your password:</p>
            <p><a href='$resetLink'>Reset Password</a></p>
        "
    );
}
```

## 🔍 Debugging

### Enable SMTP Debug Output

```php
$mailer = new PHPMailerService([
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'test@gmail.com',
    'password' => 'password',
    'debug' => true,  // <-- Shows SMTP conversation
]);
```

### Custom Logger

```php
$mailer->setLogger(function($level, $message, $context) {
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp][$level] $message " . json_encode($context) . "\n";
    file_put_contents('/var/log/mail.log', $line, FILE_APPEND);
});
```

## ⚠️ Important Notes

1. **No Queue Support**: `queue()` method falls back to immediate sending (no background jobs in pure PHP)
2. **No Mailable Support**: `sendMailable()` requires Laravel, use `send()` instead
3. **App Passwords**: For Gmail, use App Passwords (not your regular password)
4. **Error Handling**: Always wrap in try-catch for production

## 🆚 Comparison: PHPMailer vs Laravel

| Feature | PHPMailerService | LaravelMailService |
|---------|------------------|-------------------|
| **Dependencies** | ✅ Only PHPMailer | ❌ Requires Laravel |
| **Queue Support** | ❌ No (fallback to immediate) | ✅ Yes |
| **Mailable Classes** | ❌ No | ✅ Yes |
| **Multiple Recipients** | ✅ Yes | ✅ Yes |
| **Attachments** | ✅ Yes | ✅ Yes |
| **CC/BCC** | ✅ Yes | ✅ Yes |
| **Fluent Builder** | ✅ Yes | ✅ Yes |
| **Custom Logger** | ✅ Yes | ✅ Yes |
| **PHP Procedural** | ✅ Yes | ❌ No |

## 📚 See Also

- [MailBuilder API Reference](./README_MAIL.md) - Full method documentation
- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer) - Official PHPMailer docs
- [HTTP Client Standalone](../HttpClient/STANDALONE.md) - Similar architecture

---

**Summary:** Le Mail Service est conçu pour être **100% interchangeable**. Utilisez `PHPMailerService` en PHP procédural, `LaravelMailService` dans Laravel, ou créez votre propre implémentation (SendGrid, Mailgun, etc.). **Même API, différentes implémentations !** 🎯
