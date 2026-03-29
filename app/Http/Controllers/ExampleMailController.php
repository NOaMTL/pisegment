<?php

namespace App\Http\Controllers;

use App\Services\Mail\MailServiceInterface;

/**
 * Exemple d'utilisation du MailService
 *
 * Vous pouvez utiliser ce service dans n'importe quel contrôleur, job, ou commande
 */
class ExampleMailController extends Controller
{
    public function __construct(
        private MailServiceInterface $mailService
    ) {}

    /**
     * Exemple 1: Envoyer un email simple
     */
    public function sendSimpleEmail()
    {
        $success = $this->mailService->send(
            to: 'user@example.com',
            subject: 'Bienvenue sur notre plateforme',
            body: '<h1>Bonjour!</h1><p>Merci de vous être inscrit.</p>'
        );

        return response()->json(['success' => $success]);
    }

    /**
     * Exemple 2: Envoyer un email avec options (CC, BCC, attachments)
     */
    public function sendEmailWithOptions()
    {
        $success = $this->mailService->send(
            to: 'user@example.com',
            subject: 'Votre rapport mensuel',
            body: '<h1>Rapport du mois</h1><p>Voir le fichier joint.</p>',
            options: [
                'cc' => ['manager@example.com'],
                'bcc' => ['archive@example.com'],
                'attachments' => [
                    storage_path('app/reports/monthly.pdf'),
                    [
                        'path' => storage_path('app/reports/details.xlsx'),
                        'options' => [
                            'as' => 'rapport-details.xlsx',
                            'mime' => 'application/vnd.ms-excel',
                        ],
                    ],
                ],
                'replyTo' => [
                    'email' => 'support@example.com',
                    'name' => 'Support Team',
                ],
            ]
        );

        return response()->json(['success' => $success]);
    }

    /**
     * Exemple 3: Envoyer un email en masse
     */
    public function sendBulkEmail()
    {
        $recipients = [
            'user1@example.com',
            'user2@example.com',
            'user3@example.com',
        ];

        $success = $this->mailService->sendBulk(
            recipients: $recipients,
            subject: 'Newsletter de mars',
            body: '<h1>Nos dernières actualités</h1><p>Découvrez les nouveautés du mois.</p>'
        );

        return response()->json(['success' => $success]);
    }

    /**
     * Exemple 4: Mettre un email en queue (envoi différé)
     */
    public function queueEmail()
    {
        $success = $this->mailService->queue(
            to: 'user@example.com',
            subject: 'Confirmation de votre commande',
            body: '<h1>Commande confirmée</h1><p>Votre commande sera traitée sous 24h.</p>'
        );

        return response()->json(['success' => $success]);
    }

    /**
     * Exemple 5: Utiliser une Mailable classe personnalisée
     */
    public function sendWithMailable()
    {
        // Créez d'abord votre Mailable avec: php artisan make:mail WelcomeMail
        // $mailable = new \App\Mail\WelcomeMail($user);

        // $success = $this->mailService->sendMailable(
        //     to: 'user@example.com',
        //     mailable: $mailable
        // );

        return response()->json(['message' => 'Exemple commenté - créez votre Mailable d\'abord']);
    }
}

/**
 * COMMENT CHANGER DE PROVIDER:
 *
 * Pour utiliser un autre provider (SendGrid, Mailgun API directe, etc.):
 *
 * 1. Créez une nouvelle classe qui implémente MailServiceInterface
 *    Exemple: app/Services/Mail/SendGridMailService.php
 *
 * 2. Modifiez AppServiceProvider.php pour utiliser votre nouvelle implémentation:
 *    $this->app->bind(MailServiceInterface::class, SendGridMailService::class);
 *
 * 3. Votre code existant continuera de fonctionner sans modification!
 *
 * Exemple de création d'un nouveau service:
 *
 * ```php
 * namespace App\Services\Mail;
 *
 * use SendGrid\Mail\Mail;
 * use SendGrid;
 *
 * class SendGridMailService implements MailServiceInterface
 * {
 *     private SendGrid $client;
 *
 *     public function __construct()
 *     {
 *         $this->client = new SendGrid(config('services.sendgrid.api_key'));
 *     }
 *
 *     public function send(string $to, string $subject, string $body, array $options = []): bool
 *     {
 *         $email = new Mail();
 *         $email->setFrom(config('mail.from.address'));
 *         $email->addTo($to);
 *         $email->setSubject($subject);
 *         $email->addContent("text/html", $body);
 *
 *         try {
 *             $this->client->send($email);
 *             return true;
 *         } catch (\Exception $e) {
 *             \Log::error('SendGrid error: ' . $e->getMessage());
 *             return false;
 *         }
 *     }
 *
 *     // ... implémentez les autres méthodes
 * }
 * ```
 */
