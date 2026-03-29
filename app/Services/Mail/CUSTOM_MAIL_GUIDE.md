# Guide d'Intégration - Votre Classe Mail Personnalisée

Ce guide explique comment adapter `CustomMailService` pour utiliser **votre propre classe `Mail`**.

## 🎯 Objectif

Vous avez votre propre classe `Mail` qui fonctionne comme ceci :
```php
Mail::send(...);
```

On veut l'intégrer avec notre architecture interchangeable via `MailServiceInterface`.

## 📁 Fichiers à Utiliser

Copiez ces 3 fichiers dans votre projet PHP procédural :

```
app/Services/Mail/
    ├── MailServiceInterface.php    (interface commune)
    ├── MailBuilder.php             (builder fluent - 100% portable)
    └── CustomMailService.php       (adaptateur pour votre Mail)
```

## 🔧 Étape 1 : Adapter la Méthode `callYourMailClass()`

Ouvrez `CustomMailService.php` et modifiez la méthode `callYourMailClass()` selon l'API de votre classe `Mail`.

### Scénario A : Mail::send() avec paramètres individuels

Si votre `Mail::send()` ressemble à ça :
```php
Mail::send($to, $subject, $body, $cc, $bcc, $attachments);
```

**Modifiez `callYourMailClass()` ainsi :**
```php
private function callYourMailClass(array $mailData): bool
{
    return \Mail::send(
        $mailData['to'],
        $mailData['subject'],
        $mailData['body'],
        $mailData['cc'] ?? [],
        $mailData['bcc'] ?? [],
        $mailData['attachments'] ?? []
    );
}
```

### Scénario B : Mail::send() avec un array de config

Si votre `Mail::send()` accepte un array :
```php
Mail::send([
    'to' => [...],
    'subject' => '...',
    'body' => '...',
]);
```

**Modifiez `callYourMailClass()` ainsi :**
```php
private function callYourMailClass(array $mailData): bool
{
    return \Mail::send($mailData);
}
```

### Scénario C : Mail avec pattern Builder

Si votre classe `Mail` fonctionne comme ça :
```php
$mail = new Mail();
$mail->to('user@example.com')
     ->subject('Subject')
     ->body('Body')
     ->attach('/path/to/file.pdf')
     ->send();
```

**Modifiez `callYourMailClass()` ainsi :**
```php
private function callYourMailClass(array $mailData): bool
{
    $mail = new \Mail();
    
    // Add recipients
    foreach ($mailData['to'] as $recipient) {
        if (is_array($recipient)) {
            $mail->addTo($recipient['email'], $recipient['name'] ?? null);
        } else {
            $mail->addTo($recipient);
        }
    }
    
    // Set subject and body
    $mail->setSubject($mailData['subject']);
    $mail->setBody($mailData['body']);
    
    // Add CC
    if (!empty($mailData['cc'])) {
        foreach ($mailData['cc'] as $cc) {
            if ($cc) $mail->addCC($cc);
        }
    }
    
    // Add BCC
    if (!empty($mailData['bcc'])) {
        foreach ($mailData['bcc'] as $bcc) {
            if ($bcc) $mail->addBCC($bcc);
        }
    }
    
    // Add attachments
    if (!empty($mailData['attachments'])) {
        foreach ($mailData['attachments'] as $attachment) {
            if (is_string($attachment)) {
                // Simple file path
                $mail->addAttachment($attachment);
            } elseif (is_array($attachment)) {
                if (isset($attachment['type']) && $attachment['type'] === 'data') {
                    // Attachment from data (if your Mail supports it)
                    $mail->addAttachmentFromData(
                        $attachment['data'],
                        $attachment['name']
                    );
                } elseif (isset($attachment['path'])) {
                    // File with options
                    $mail->addAttachment($attachment['path']);
                }
            }
        }
    }
    
    // Set From (if provided)
    if (isset($mailData['from'])) {
        $from = $mailData['from'];
        if (is_array($from)) {
            $mail->setFrom($from['email'], $from['name'] ?? null);
        } else {
            $mail->setFrom($from);
        }
    }
    
    // Set Reply-To (if provided)
    if (isset($mailData['replyTo'])) {
        $replyTo = $mailData['replyTo'];
        if (is_array($replyTo)) {
            $mail->setReplyTo($replyTo['email'], $replyTo['name'] ?? null);
        } else {
            $mail->setReplyTo($replyTo);
        }
    }
    
    return $mail->send();
}
```

### Scénario D : Mail::send() simple (un destinataire à la fois)

Si votre `Mail::send()` ne gère qu'un destinataire :
```php
Mail::send('user@example.com', 'Subject', 'Body');
```

**Modifiez `callYourMailClass()` ainsi :**
```php
private function callYourMailClass(array $mailData): bool
{
    // Envoyer un email pour chaque destinataire
    $allSuccess = true;
    
    foreach ($mailData['to'] as $recipient) {
        $email = is_array($recipient) ? $recipient['email'] : $recipient;
        
        $success = \Mail::send(
            $email,
            $mailData['subject'],
            $mailData['body']
        );
        
        if (!$success) {
            $allSuccess = false;
        }
    }
    
    return $allSuccess;
}
```

## 🚀 Étape 2 : Utilisation dans Votre Projet

Une fois `CustomMailService` adapté, utilisez-le comme n'importe quel autre service :

### Usage Direct

```php
<?php

require 'vendor/autoload.php';

use App\Services\Mail\CustomMailService;

$mailService = new CustomMailService();

// Simple email
$mailService->send(
    to: 'user@example.com',
    subject: 'Bienvenue',
    body: '<h1>Bienvenue sur notre plateforme !</h1>'
);

// Avec options
$mailService->send(
    to: ['user@example.com', 'admin@example.com'],
    subject: 'Rapport mensuel',
    body: '<h1>Rapport</h1><p>Voir pièce jointe.</p>',
    options: [
        'cc' => 'manager@example.com',
        'bcc' => 'archive@example.com',
        'attachments' => ['/path/to/report.pdf'],
    ]
);
```

### Usage avec Builder (Recommandé)

```php
$mailService->builder()
    ->to('user@example.com')
    ->cc('manager@example.com')
    ->bcc('archive@example.com')
    ->subject('Rapport mensuel')
    ->html('<h1>Rapport</h1><p>Voir pièce jointe.</p>')
    ->attach('/path/to/report.pdf')
    ->attach('/path/to/data.xlsx')
    ->send();
```

### Usage dans un Cron

```php
#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use App\Services\Mail\CustomMailService;

$mailService = new CustomMailService();

// Ajouter un logger personnalisé
$mailService->setLogger(function($level, $message, $context) {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp][$level] $message\n";
    file_put_contents('/var/log/mail-cron.log', $log, FILE_APPEND);
});

// Envoyer le rapport
$reportData = generateDailyReport();

$result = $mailService->builder()
    ->to(['manager@company.com', 'director@company.com'])
    ->subject('Rapport Quotidien - ' . date('Y-m-d'))
    ->html($reportData)
    ->send();

if ($result) {
    echo "✅ Rapport envoyé avec succès\n";
} else {
    echo "❌ Échec de l'envoi du rapport\n";
    exit(1);
}
```

## 🔄 Avantages de Cette Architecture

### 1. Interchangeable
Vous pouvez changer d'implémentation sans toucher à votre code :

```php
// Dans Laravel
$mail = new LaravelMailService();

// En PHP procédural avec PHPMailer
$mail = new PHPMailerService([...]);

// Avec votre classe Mail personnalisée
$mail = new CustomMailService();

// Même API partout !
$mail->send(...);
$mail->builder()->to(...)->send();
```

### 2. Testable
Vous pouvez créer un mock pour les tests :

```php
class MockMailService implements MailServiceInterface
{
    public array $sentEmails = [];
    
    public function send(string|array $to, string $subject, string $body, array $options = []): bool
    {
        $this->sentEmails[] = compact('to', 'subject', 'body', 'options');
        return true;
    }
    
    // ... autres méthodes
}

// Dans vos tests
$mockMail = new MockMailService();
$result = sendWelcomeEmail($mockMail, $user);
assert(count($mockMail->sentEmails) === 1);
```

### 3. Réutilisable
Le `MailBuilder` est identique partout, vous gardez la même syntax :

```php
// Même code, différentes implémentations
$mailService->builder()
    ->to('user@example.com')
    ->subject('Test')
    ->html('<h1>Hello</h1>')
    ->send();
```

## 📚 Exemples Complets

### Exemple 1 : Envoi de Facture

```php
function sendInvoice($invoice, $mailService) {
    $pdfPath = generateInvoicePDF($invoice);
    
    return $mailService->builder()
        ->to($invoice['client_email'])
        ->cc($invoice['manager_email'])
        ->subject("Facture #{$invoice['number']}")
        ->html("
            <h1>Facture #{$invoice['number']}</h1>
            <p>Montant: {$invoice['amount']} €</p>
            <p>Échéance: {$invoice['due_date']}</p>
        ")
        ->attach($pdfPath)
        ->send();
}

$mailService = new CustomMailService();
sendInvoice($invoice, $mailService);
```

### Exemple 2 : Newsletter

```php
function sendNewsletter($subscribers, $content, $mailService) {
    $recipients = array_column($subscribers, 'email');
    
    return $mailService->sendBulk(
        recipients: $recipients,
        subject: 'Newsletter - ' . date('F Y'),
        body: $content
    );
}

$mailService = new CustomMailService();
sendNewsletter($subscribers, $newsletterHTML, $mailService);
```

### Exemple 3 : Notification avec Plusieurs Pièces Jointes

```php
function notifyTeam($message, $files, $mailService) {
    return $mailService->builder()
        ->to(['dev@company.com', 'ops@company.com'])
        ->cc('manager@company.com')
        ->subject('Notification Système')
        ->html("<h2>Notification</h2><p>$message</p>")
        ->attachMany($files)
        ->send();
}

$mailService = new CustomMailService();
notifyTeam('Backup terminé', ['/var/backup/db.sql', '/var/backup/files.tar.gz'], $mailService);
```

## ✅ Checklist d'Intégration

- [ ] Copier les 3 fichiers (Interface, Builder, CustomMailService)
- [ ] Adapter `callYourMailClass()` selon l'API de votre classe Mail
- [ ] Tester l'envoi d'un email simple
- [ ] Tester l'envoi avec CC/BCC
- [ ] Tester l'envoi avec pièces jointes
- [ ] Tester l'envoi à plusieurs destinataires
- [ ] Ajouter un logger personnalisé (optionnel)
- [ ] Intégrer dans vos crons

## 📖 Documentation Complète

- [README_MAIL.md](./README_MAIL.md) - Documentation complète du Mail Service
- [QUICKSTART_MAIL.md](./QUICKSTART_MAIL.md) - Guide de démarrage rapide
- [example-standalone.php](./example-standalone.php) - 13 exemples complets

---

**Astuce Pro :** Si votre classe `Mail` change, vous n'avez qu'à modifier la méthode `callYourMailClass()` dans `CustomMailService`. Tout le reste de votre code continue de fonctionner ! 🚀
