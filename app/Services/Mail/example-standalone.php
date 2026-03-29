<?php

/**
 * STANDALONE MAIL SERVICE EXAMPLES
 * 
 * This file demonstrates the Mail Service in pure PHP (no Laravel required).
 * Perfect for procedural PHP projects, cron jobs, or standalone scripts.
 * 
 * Requirements:
 * - composer require phpmailer/phpmailer
 * 
 * Copy these 3 files to your project:
 * - MailServiceInterface.php
 * - MailBuilder.php
 * - PHPMailerService.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use App\Services\Mail\PHPMailerService;

// ============================================
// CONFIGURATION
// ============================================

$mailService = new PHPMailerService([
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your@gmail.com',
    'password' => 'your-app-password',  // Use App Password for Gmail
    'encryption' => 'tls',
    'from' => [
        'email' => 'noreply@myapp.com',
        'name' => 'My Application'
    ],
    'debug' => false,  // Set true to see SMTP debug output
]);

// Optional: Add custom logger
$mailService->setLogger(function($level, $message, $context) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp][$level] $message\n";
    if (!empty($context)) {
        echo "  Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
});

// ============================================
// EXAMPLE 1: Simple Email
// ============================================
echo "\n=== EXAMPLE 1: Simple Email ===\n";

$result = $mailService->send(
    to: 'user@example.com',
    subject: 'Welcome to Our Platform',
    body: '<h1>Welcome!</h1><p>Thank you for joining us.</p>'
);

echo $result ? "✅ Email sent successfully!\n" : "❌ Failed to send email\n";

// ============================================
// EXAMPLE 2: Multiple Recipients
// ============================================
echo "\n=== EXAMPLE 2: Multiple Recipients ===\n";

$result = $mailService->send(
    to: [
        'user1@example.com',
        'user2@example.com',
        'user3@example.com',
    ],
    subject: 'Team Announcement',
    body: '<p>Important update for the entire team...</p>'
);

echo $result ? "✅ Email sent to all recipients!\n" : "❌ Failed to send email\n";

// ============================================
// EXAMPLE 3: Multiple Recipients with Names
// ============================================
echo "\n=== EXAMPLE 3: Recipients with Names ===\n";

$result = $mailService->send(
    to: [
        ['email' => 'john@example.com', 'name' => 'John Doe'],
        ['email' => 'jane@example.com', 'name' => 'Jane Smith'],
        ['email' => 'bob@example.com', 'name' => 'Bob Johnson'],
    ],
    subject: 'Personal Invitation',
    body: '<h2>You are Invited!</h2><p>We would love to have you join us.</p>'
);

echo $result ? "✅ Personalized emails sent!\n" : "❌ Failed to send email\n";

// ============================================
// EXAMPLE 4: Email with CC and BCC
// ============================================
echo "\n=== EXAMPLE 4: CC and BCC ===\n";

$result = $mailService->send(
    to: 'client@company.com',
    subject: 'Project Update',
    body: '<p>The project is progressing well...</p>',
    options: [
        'cc' => 'manager@company.com',
        'bcc' => ['archive@company.com', 'compliance@company.com'],
    ]
);

echo $result ? "✅ Email sent with CC/BCC!\n" : "❌ Failed to send email\n";

// ============================================
// EXAMPLE 5: Email with File Attachments
// ============================================
echo "\n=== EXAMPLE 5: File Attachments ===\n";

// Create a temporary file for demonstration
$tempFile = sys_get_temp_dir() . '/sample-document.txt';
file_put_contents($tempFile, "This is a sample document.\n\nGenerated at: " . date('Y-m-d H:i:s'));

$result = $mailService->send(
    to: 'user@example.com',
    subject: 'Documents Attached',
    body: '<p>Please find the requested documents attached.</p>',
    options: [
        'attachments' => [
            $tempFile,  // Simple file path
        ]
    ]
);

// Clean up
unlink($tempFile);

echo $result ? "✅ Email with attachment sent!\n" : "❌ Failed to send email\n";

// ============================================
// EXAMPLE 6: Multiple Attachments with Custom Names
// ============================================
echo "\n=== EXAMPLE 6: Multiple Attachments ===\n";

// Create temporary files
$invoice = sys_get_temp_dir() . '/invoice.txt';
$report = sys_get_temp_dir() . '/report.txt';
file_put_contents($invoice, "INVOICE #2024-001\nAmount: $150.00");
file_put_contents($report, "MONTHLY REPORT\nSales: 100 units");

$result = $mailService->send(
    to: 'client@example.com',
    subject: 'Invoice and Report',
    body: '<h2>Monthly Documents</h2><p>Invoice and report attached.</p>',
    options: [
        'attachments' => [
            [
                'path' => $invoice,
                'options' => ['as' => 'Invoice-2024-001.txt']
            ],
            [
                'path' => $report,
                'options' => ['as' => 'Report-January.txt']
            ],
        ]
    ]
);

// Clean up
unlink($invoice);
unlink($report);

echo $result ? "✅ Multiple attachments sent!\n" : "❌ Failed to send email\n";

// ============================================
// EXAMPLE 7: Attachment from String (Generated PDF)
// ============================================
echo "\n=== EXAMPLE 7: Attachment from String Data ===\n";

// Simulate PDF generation (in real app, use TCPDF, FPDF, etc.)
$pdfContent = "%PDF-1.4\nThis would be actual PDF binary content...";

$result = $mailService->send(
    to: 'user@example.com',
    subject: 'Generated Report',
    body: '<p>Your custom report has been generated and is attached.</p>',
    options: [
        'attachments' => [
            [
                'type' => 'data',
                'data' => $pdfContent,
                'name' => 'report-' . date('Y-m-d') . '.pdf',
                'options' => ['mime' => 'application/pdf']
            ]
        ]
    ]
);

echo $result ? "✅ Generated PDF sent!\n" : "❌ Failed to send email\n";

// ============================================
// EXAMPLE 8: Fluent Builder API
// ============================================
echo "\n=== EXAMPLE 8: Fluent Builder ===\n";

$result = $mailService->builder()
    ->to('user@example.com')
    ->cc(['manager@example.com', 'supervisor@example.com'])
    ->bcc('archive@example.com')
    ->subject('Quarterly Report')
    ->html('
        <h1>Q1 2024 Report</h1>
        <p>Please review the attached quarterly report.</p>
        <ul>
            <li>Revenue: $1.2M</li>
            <li>Growth: +25%</li>
            <li>Customers: 5,000</li>
        </ul>
    ')
    ->from(['email' => 'reports@company.com', 'name' => 'Reports System'])
    ->replyTo('support@company.com')
    ->send();

echo $result ? "✅ Fluent email sent!\n" : "❌ Failed to send email\n";

// ============================================
// EXAMPLE 9: Builder with Multiple Attachments
// ============================================
echo "\n=== EXAMPLE 9: Builder with Attachments ===\n";

// Create temp files
$file1 = sys_get_temp_dir() . '/file1.txt';
$file2 = sys_get_temp_dir() . '/file2.txt';
$file3 = sys_get_temp_dir() . '/file3.txt';
file_put_contents($file1, "File 1 content");
file_put_contents($file2, "File 2 content");
file_put_contents($file3, "File 3 content");

$result = $mailService->builder()
    ->to('client@example.com')
    ->subject('Project Files')
    ->html('<p>All project files are attached.</p>')
    ->attachMany([
        $file1,
        $file2,
        $file3,
    ])
    ->send();

// Clean up
unlink($file1);
unlink($file2);
unlink($file3);

echo $result ? "✅ Multiple files sent via builder!\n" : "❌ Failed to send email\n";

// ============================================
// EXAMPLE 10: Send Bulk (Individual Emails)
// ============================================
echo "\n=== EXAMPLE 10: Bulk Send (Individual Emails) ===\n";

$recipients = [
    'user1@example.com',
    'user2@example.com',
    'user3@example.com',
];

$result = $mailService->sendBulk(
    recipients: $recipients,
    subject: 'Personal Newsletter',
    body: '
        <h1>Monthly Newsletter</h1>
        <p>This email was sent just to you.</p>
        <p>Click here to unsubscribe.</p>
    ',
    options: [
        'from' => ['email' => 'newsletter@company.com', 'name' => 'Newsletter']
    ]
);

echo $result ? "✅ Bulk emails sent!\n" : "❌ Some emails failed\n";

// ============================================
// EXAMPLE 11: Complex Email (All Features)
// ============================================
echo "\n=== EXAMPLE 11: Complex Email (All Features) ===\n";

// Create attachment
$attachment = sys_get_temp_dir() . '/invoice.txt';
file_put_contents($attachment, "INVOICE #2024-999\nTotal: $500.00");

$result = $mailService->builder()
    ->to([
        ['email' => 'client@company.com', 'name' => 'Acme Corp'],
    ])
    ->cc([
        ['email' => 'manager@mycompany.com', 'name' => 'Sales Manager'],
        'supervisor@mycompany.com',
    ])
    ->bcc([
        'accounting@mycompany.com',
        'archive@mycompany.com',
    ])
    ->subject('Invoice #2024-999')
    ->html('
        <h1>Invoice</h1>
        <p>Dear Acme Corp,</p>
        <p>Please find your invoice attached.</p>
        <p><strong>Amount Due:</strong> $500.00</p>
        <p><strong>Due Date:</strong> ' . date('Y-m-d', strtotime('+30 days')) . '</p>
        <p>Thank you for your business!</p>
    ')
    ->attach($attachment)
    ->from(['email' => 'billing@mycompany.com', 'name' => 'Billing Department'])
    ->replyTo(['email' => 'support@mycompany.com', 'name' => 'Customer Support'])
    ->send();

// Clean up
unlink($attachment);

echo $result ? "✅ Complex email sent!\n" : "❌ Failed to send email\n";

// ============================================
// EXAMPLE 12: Real-World Use Case - Order Confirmation
// ============================================
echo "\n=== EXAMPLE 12: Order Confirmation ===\n";

function sendOrderConfirmation($mailService, $order) {
    // Generate invoice PDF (simulated)
    $invoicePDF = "PDF content for order #{$order['id']}";
    
    $result = $mailService->builder()
        ->to($order['customer']['email'])
        ->subject("Order Confirmation - #{$order['id']}")
        ->html("
            <h1>Thank You for Your Order!</h1>
            <p>Hi {$order['customer']['name']},</p>
            <p>Your order #{$order['id']} has been confirmed.</p>
            <h2>Order Details:</h2>
            <ul>
                <li>Order ID: #{$order['id']}</li>
                <li>Total: \${$order['total']}</li>
                <li>Status: {$order['status']}</li>
            </ul>
            <p>Invoice attached.</p>
        ")
        ->attachData(
            data: $invoicePDF,
            name: "invoice-{$order['id']}.pdf",
            options: ['mime' => 'application/pdf']
        )
        ->from(['email' => 'orders@shop.com', 'name' => 'Our Shop'])
        ->send();
    
    return $result;
}

$order = [
    'id' => 'ORD-2024-001',
    'customer' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
    'total' => 149.99,
    'status' => 'Confirmed',
];

$result = sendOrderConfirmation($mailService, $order);
echo $result ? "✅ Order confirmation sent!\n" : "❌ Failed to send confirmation\n";

// ============================================
// EXAMPLE 13: Debugging Email Contents
// ============================================
echo "\n=== EXAMPLE 13: Debug Builder Contents ===\n";

$builder = $mailService->builder()
    ->to(['user1@example.com', 'user2@example.com'])
    ->cc('manager@example.com')
    ->bcc(['archive@example.com', 'compliance@example.com'])
    ->subject('Test Email')
    ->html('<h1>HTML Content</h1>')
    ->text('Plain text fallback')
    ->from(['email' => 'sender@example.com', 'name' => 'Sender Name'])
    ->replyTo('support@example.com');

// View what will be sent (without sending)
$data = $builder->toArray();
echo "Email data:\n";
print_r($data);

// Now actually send it
$result = $builder->send();
echo $result ? "✅ Debug email sent!\n" : "❌ Failed to send email\n";

// ============================================
// SUMMARY
// ============================================
echo "\n" . str_repeat("=", 50) . "\n";
echo "ALL EXAMPLES COMPLETED!\n";
echo str_repeat("=", 50) . "\n\n";

echo "Key Features Demonstrated:\n";
echo "✅ Simple emails\n";
echo "✅ Multiple recipients (array of emails)\n";
echo "✅ Recipients with names\n";
echo "✅ CC and BCC\n";
echo "✅ File attachments\n";
echo "✅ Multiple attachments with custom names\n";
echo "✅ Attachments from string data (generated content)\n";
echo "✅ Fluent builder API\n";
echo "✅ Bulk sending (individual emails)\n";
echo "✅ Complex emails with all features\n";
echo "✅ Real-world use case (order confirmation)\n";
echo "✅ Debugging email contents\n";
echo "✅ Custom logging\n";
echo "\n";

echo "🎯 This Mail Service is 100% standalone!\n";
echo "   No Laravel required - works in any PHP project.\n";
echo "\n";

echo "🔄 Interchangeable Implementations:\n";
echo "   - PHPMailerService (this example)\n";
echo "   - LaravelMailService (for Laravel projects)\n";
echo "   - Create your own: SendGridService, MailgunService, etc.\n";
echo "\n";

echo "📚 See STANDALONE_MAIL.md for full documentation.\n";
