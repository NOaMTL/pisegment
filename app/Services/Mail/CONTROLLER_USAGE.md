# Utilisation du Mail Service dans vos Contrôleurs

Guide pratique pour utiliser le Mail Service avec architecture interchangeable.

## 🎯 Le Principe

**1 seul changement = Changer d'implémentation partout**

```
┌─────────────────────────────────────────────────────────────┐
│ AppServiceProvider.php                                       │
│ ════════════════════════════════════════════════════════════ │
│                                                               │
│  bind(MailServiceInterface::class, CustomMailService::class) │ ← Changez ici
│                                                               │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────────┐
        │ Tous vos contrôleurs utilisent          │
        │ l'implémentation configurée             │
        │ automatiquement !                        │
        └─────────────────────────────────────────┘
```

## 🚀 Configuration Initiale

### Étape 1 : Choisir votre implémentation

**Ouvrez [app/Providers/AppServiceProvider.php](../Providers/AppServiceProvider.php)**

```php
public function register(): void
{
    // AUJOURD'HUI : Utilisez votre classe Mail
    $this->app->bind(MailServiceInterface::class, CustomMailService::class);
    
    // DEMAIN : Changez pour Laravel Mail
    // $this->app->bind(MailServiceInterface::class, LaravelMailService::class);
    
    // APRÈS-DEMAIN : Changez pour PHPMailer
    // $this->app->bind(MailServiceInterface::class, function ($app) {
    //     return new PHPMailerService([...config...]);
    // });
}
```

### Étape 2 : Adaptez CustomMailService à votre API Mail

**Ouvrez [app/Services/Mail/CustomMailService.php](../Services/Mail/CustomMailService.php)**

Modifiez la méthode `callYourMailClass()` :

```php
private function callYourMailClass(array $mailData): bool
{
    // REMPLACEZ par l'appel à VOTRE classe Mail
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

## 📝 Utilisation dans les Contrôleurs

### Exemple 1 : Injection de Dépendance (Recommandé)

```php
<?php

namespace App\Http\Controllers;

use App\Services\Mail\MailServiceInterface;

class YourController extends Controller
{
    public function __construct(
        private MailServiceInterface $mailService  // ← Laravel résout automatiquement
    ) {}
    
    public function sendEmail()
    {
        $this->mailService->send(
            to: 'user@example.com',
            subject: 'Hello',
            body: '<h1>Welcome!</h1>'
        );
    }
}
```

### Exemple 2 : Via le Service Container

```php
public function sendEmail()
{
    $mailService = app(MailServiceInterface::class);
    
    $mailService->send(
        to: 'user@example.com',
        subject: 'Hello',
        body: '<h1>Welcome!</h1>'
    );
}
```

### Exemple 3 : Avec le Builder (Plus Fluide)

```php
public function sendInvoice($userId)
{
    $user = User::findOrFail($userId);
    
    $this->mailService->builder()
        ->to($user->email)
        ->cc('manager@company.com')
        ->subject('Votre facture')
        ->html(view('emails.invoice', ['user' => $user])->render())
        ->attach(storage_path('invoices/invoice.pdf'))
        ->send();
}
```

## 📂 Exemple Complet de Contrôleur

Voir [app/Http/Controllers/NotificationController.php](../Http/Controllers/NotificationController.php) pour un exemple complet avec :
- ✅ Email simple
- ✅ Email avec pièces jointes
- ✅ Email à plusieurs destinataires
- ✅ Email avec options avancées (CC, BCC, ReplyTo)
- ✅ Email en queue
- ✅ Gestion d'erreurs

## 🔄 Changer d'Implémentation

### Aujourd'hui : Votre classe Mail

```php
// AppServiceProvider.php
$this->app->bind(MailServiceInterface::class, CustomMailService::class);
```

**Votre contrôleur :**
```php
$this->mailService->send('user@example.com', 'Subject', 'Body');
// Utilise votre Mail::send() en arrière-plan
```

### Demain : Laravel Mail

```php
// AppServiceProvider.php
$this->app->bind(MailServiceInterface::class, LaravelMailService::class);
```

**Votre contrôleur (AUCUN CHANGEMENT) :**
```php
$this->mailService->send('user@example.com', 'Subject', 'Body');
// Utilise Laravel Mail en arrière-plan maintenant
```

### Après-demain : PHPMailer

```php
// AppServiceProvider.php
$this->app->bind(MailServiceInterface::class, function ($app) {
    return new PHPMailerService([
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => config('mail.mailers.smtp.username'),
        'password' => config('mail.mailers.smtp.password'),
    ]);
});
```

**Votre contrôleur (TOUJOURS AUCUN CHANGEMENT) :**
```php
$this->mailService->send('user@example.com', 'Subject', 'Body');
// Utilise PHPMailer en arrière-plan maintenant
```

## 🎯 Cas d'Usage Réels

### 1. Envoyer une confirmation de commande

```php
public function sendOrderConfirmation(Order $order)
{
    return $this->mailService->builder()
        ->to($order->customer->email)
        ->subject("Commande #{$order->id} confirmée")
        ->html(view('emails.order-confirmation', ['order' => $order])->render())
        ->attach($order->invoicePath)
        ->send();
}
```

### 2. Envoyer un rapport à l'équipe

```php
public function sendTeamReport()
{
    $team = User::where('role', 'manager')->get();
    
    $reportData = $this->generateWeeklyReport();
    
    return $this->mailService->builder()
        ->to($team->pluck('email')->toArray())
        ->subject('Rapport Hebdomadaire - ' . now()->format('Y-m-d'))
        ->html($reportData)
        ->send();
}
```

### 3. Envoyer un email avec plusieurs pièces jointes

```php
public function sendDocuments(User $user, array $documents)
{
    return $this->mailService->builder()
        ->to($user->email)
        ->subject('Vos documents')
        ->html('<p>Veuillez trouver ci-joint vos documents.</p>')
        ->attachMany($documents)  // ['path/to/file1.pdf', 'path/to/file2.pdf']
        ->send();
}
```

### 4. Envoyer un PDF généré à la volée

```php
public function sendGeneratedReport(User $user)
{
    // Générer le PDF
    $pdf = \PDF::loadView('reports.monthly', ['user' => $user]);
    $pdfContent = $pdf->output();
    
    return $this->mailService->builder()
        ->to($user->email)
        ->subject('Rapport Mensuel')
        ->html('<p>Votre rapport mensuel est joint.</p>')
        ->attachData($pdfContent, 'rapport-' . now()->format('Y-m') . '.pdf', [
            'mime' => 'application/pdf'
        ])
        ->send();
}
```

### 5. Newsletter à tous les abonnés

```php
public function sendNewsletter(string $content)
{
    $subscribers = User::where('subscribed', true)->pluck('email')->toArray();
    
    return $this->mailService->sendBulk(
        recipients: $subscribers,
        subject: 'Newsletter - ' . now()->format('F Y'),
        body: $content
    );
}
```

## ✅ Avantages de Cette Architecture

### 🔄 Flexibilité Totale
- Changez d'implémentation en 1 ligne
- Aucun changement dans vos contrôleurs
- Testez différentes solutions facilement

### 🧪 Facilité de Test
```php
// Dans vos tests
class TestMailService implements MailServiceInterface {
    public $sentEmails = [];
    
    public function send(...$args): bool {
        $this->sentEmails[] = $args;
        return true;
    }
}

// Bind le mock
$this->app->bind(MailServiceInterface::class, TestMailService::class);

// Testez votre contrôleur
$response = $this->post('/send-email', [...]);

// Vérifiez
$mockMail = app(MailServiceInterface::class);
$this->assertCount(1, $mockMail->sentEmails);
```

### 📦 Code Réutilisable
- Même code fonctionne dans Laravel ET PHP procédural
- Copiez MailServiceInterface + MailBuilder + CustomMailService
- Utilisez dans vos scripts cron standalone

### 🎯 Séparation des Responsabilités
- Contrôleur : gère la logique métier
- MailService : gère l'envoi technique
- AppServiceProvider : configure quelle implémentation utiliser

## 📚 Documentation Complète

- **[CustomMailService.php](../Services/Mail/CustomMailService.php)** - Implémentation pour votre classe Mail
- **[CUSTOM_MAIL_GUIDE.md](../Services/Mail/CUSTOM_MAIL_GUIDE.md)** - Guide d'adaptation complet
- **[NotificationController.php](../Http/Controllers/NotificationController.php)** - Exemples d'utilisation
- **[README_MAIL.md](../Services/Mail/README_MAIL.md)** - Documentation API complète
- **[QUICKSTART_MAIL.md](../Services/Mail/QUICKSTART_MAIL.md)** - Guide démarrage rapide

---

**En résumé :** 
1. ✅ Configurez l'implémentation dans `AppServiceProvider`
2. ✅ Injectez `MailServiceInterface` dans vos contrôleurs
3. ✅ Utilisez `$this->mailService->send()` ou `->builder()`
4. ✅ Changez d'implémentation quand vous voulez sans toucher aux contrôleurs !

🚀 **Votre code reste le même, seule l'implémentation change !**
