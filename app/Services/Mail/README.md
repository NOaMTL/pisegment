# Service d'envoi d'emails

Service abstrait pour l'envoi d'emails permettant de changer facilement de provider sans modifier le code existant.

## Structure

```
app/Services/Mail/
├── MailServiceInterface.php      # Interface définissant le contrat
└── LaravelMailService.php        # Implémentation par défaut (Laravel Mail)
```

## Utilisation

### Injection de dépendances (recommandé)

```php
use App\Services\Mail\MailServiceInterface;

class YourController extends Controller
{
    public function __construct(
        private MailServiceInterface $mailService
    ) {}

    public function sendWelcome()
    {
        $this->mailService->send(
            to: 'user@example.com',
            subject: 'Bienvenue',
            body: '<h1>Bonjour!</h1>'
        );
    }
}
```

### Via le service container

```php
$mailService = app(MailServiceInterface::class);
$mailService->send('user@example.com', 'Sujet', 'Corps du message');
```

## Méthodes disponibles

### `send()` - Envoi simple

```php
$mailService->send(
    to: 'user@example.com',
    subject: 'Sujet de l\'email',
    body: '<h1>Titre</h1><p>Contenu HTML</p>',
    options: [
        'cc' => ['cc@example.com'],
        'bcc' => ['bcc@example.com'],
        'from' => [
            'email' => 'noreply@example.com',
            'name' => 'Mon Application'
        ],
        'replyTo' => [
            'email' => 'support@example.com',
            'name' => 'Support'
        ],
        'attachments' => [
            '/path/to/file.pdf',
            [
                'path' => '/path/to/file.xlsx',
                'options' => [
                    'as' => 'rapport.xlsx',
                    'mime' => 'application/vnd.ms-excel'
                ]
            ]
        ]
    ]
);
```

### `sendMailable()` - Avec classe Mailable

```php
$mailable = new WelcomeMail($user);
$mailService->sendMailable('user@example.com', $mailable);
```

### `sendBulk()` - Envoi en masse

```php
$recipients = ['user1@example.com', 'user2@example.com', 'user3@example.com'];
$mailService->sendBulk(
    recipients: $recipients,
    subject: 'Newsletter',
    body: '<h1>Nos actualités</h1>'
);
```

### `queue()` - Envoi différé (via queue)

```php
$mailService->queue(
    to: 'user@example.com',
    subject: 'Confirmation',
    body: '<h1>Commande confirmée</h1>'
);
```

## Changer de provider

### 1. Créer une nouvelle implémentation

Créez votre propre classe qui implémente `MailServiceInterface`:

```php
// app/Services/Mail/SendGridMailService.php
namespace App\Services\Mail;

use SendGrid\Mail\Mail;
use SendGrid;

class SendGridMailService implements MailServiceInterface
{
    private SendGrid $client;

    public function __construct()
    {
        $this->client = new SendGrid(config('services.sendgrid.api_key'));
    }

    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        $email = new Mail();
        $email->setFrom(config('mail.from.address'));
        $email->addTo($to);
        $email->setSubject($subject);
        $email->addContent("text/html", $body);

        try {
            $this->client->send($email);
            return true;
        } catch (\Exception $e) {
            \Log::error('SendGrid error: ' . $e->getMessage());
            return false;
        }
    }

    // Implémentez les autres méthodes...
}
```

### 2. Modifier le binding dans AppServiceProvider

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    // Changez simplement cette ligne:
    $this->app->bind(MailServiceInterface::class, SendGridMailService::class);
}
```

**Votre code existant continuera de fonctionner sans modification!**

## Exemples concrets

Voir `app/Http/Controllers/ExampleMailController.php` pour des exemples complets.

## Configuration

La configuration mail se trouve dans `config/mail.php`.

Pour tester en local, utilisez Mailtrap ou MailHog:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

## Tests

```bash
# Créer un test
php artisan make:test MailServiceTest

# Exemple de test
public function test_can_send_email()
{
    $mailService = app(MailServiceInterface::class);
    
    $result = $mailService->send(
        'test@example.com',
        'Test Subject',
        'Test Body'
    );
    
    $this->assertTrue($result);
}
```

## Avantages

✅ **Abstraction** : Changez de provider sans toucher au code métier  
✅ **Testable** : Mockez facilement l'interface dans vos tests  
✅ **Maintenable** : Un seul fichier à modifier pour changer de provider  
✅ **Type-safe** : Interface claire avec typage strict  
✅ **Extensible** : Ajoutez vos propres méthodes à l'interface
