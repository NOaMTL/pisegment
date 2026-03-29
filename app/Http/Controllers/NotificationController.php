<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Mail\MailServiceInterface;
use Illuminate\Http\Request;

/**
 * Exemple d'utilisation du Mail Service dans un contrôleur
 * 
 * ✨ L'implémentation utilisée (LaravelMail, CustomMail, PHPMailer, etc.)
 *    est configurée dans AppServiceProvider
 * 
 * 💡 Vous pouvez changer d'implémentation sans toucher à ce code !
 */
class NotificationController extends Controller
{
    /**
     * Injection de dépendance - Laravel résout automatiquement
     * l'implémentation configurée dans AppServiceProvider
     */
    public function __construct(
        private MailServiceInterface $mailService
    ) {}

    /**
     * Envoyer une notification simple
     */
    public function sendWelcome(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
        ]);

        // Utilisation directe
        $result = $this->mailService->send(
            to: $validated['email'],
            subject: 'Bienvenue !',
            body: view('emails.welcome', ['name' => $validated['name']])->render()
        );

        if ($result) {
            return response()->json(['message' => 'Email envoyé avec succès']);
        }

        return response()->json(['message' => 'Échec de l\'envoi'], 500);
    }

    /**
     * Envoyer avec pièces jointes
     */
    public function sendInvoice(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        
        // Générer la facture (exemple)
        $invoicePath = storage_path('invoices/invoice-123.pdf');

        // Utilisation avec le builder (plus fluide)
        $result = $this->mailService->builder()
            ->to($user->email)
            ->cc('accounting@company.com')
            ->subject('Votre facture')
            ->html(view('emails.invoice', ['user' => $user])->render())
            ->attach($invoicePath)
            ->send();

        return response()->json([
            'sent' => $result,
            'message' => $result ? 'Facture envoyée' : 'Échec de l\'envoi'
        ]);
    }

    /**
     * Envoyer à plusieurs destinataires
     */
    public function sendBulkNewsletter(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string',
            'content' => 'required|string',
        ]);

        // Récupérer les abonnés
        $subscribers = User::where('subscribed', true)->pluck('email')->toArray();

        // Envoi groupé
        $result = $this->mailService->sendBulk(
            recipients: $subscribers,
            subject: $validated['subject'],
            body: $validated['content']
        );

        return response()->json([
            'sent' => $result,
            'count' => count($subscribers),
        ]);
    }

    /**
     * Envoyer avec options avancées
     */
    public function sendReport(Request $request)
    {
        $validated = $request->validate([
            'recipients' => 'required|array',
            'recipients.*' => 'email',
        ]);

        // Créer le rapport
        $reportContent = $this->generateReport();
        $pdfContent = $this->generateReportPDF();

        // Envoi complexe avec toutes les options
        $result = $this->mailService->builder()
            ->to($validated['recipients'])
            ->cc(['manager@company.com', 'director@company.com'])
            ->bcc('archive@company.com')
            ->subject('Rapport Mensuel - ' . now()->format('F Y'))
            ->html($reportContent)
            ->attachData($pdfContent, 'rapport-' . now()->format('Y-m') . '.pdf', [
                'mime' => 'application/pdf'
            ])
            ->from(['email' => 'reports@company.com', 'name' => 'Système de Rapports'])
            ->replyTo('support@company.com')
            ->send();

        return response()->json(['sent' => $result]);
    }

    /**
     * Envoyer en mode queue (asynchrone si supporté)
     */
    public function sendAsync(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Queue l'email (envoi en arrière-plan si Laravel, sinon envoi immédiat)
        $result = $this->mailService->queue(
            to: $validated['email'],
            subject: 'Newsletter',
            body: view('emails.newsletter')->render()
        );

        return response()->json([
            'queued' => $result,
            'message' => 'Email mis en file d\'attente'
        ]);
    }

    /**
     * Exemple avec gestion d'erreurs
     */
    public function sendWithErrorHandling(Request $request)
    {
        try {
            $result = $this->mailService->builder()
                ->to($request->input('email'))
                ->subject('Test Email')
                ->html('<h1>Ceci est un test</h1>')
                ->send();

            if (!$result) {
                return response()->json([
                    'error' => 'Email non envoyé (retour false)'
                ], 500);
            }

            return response()->json(['message' => 'Email envoyé avec succès']);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception lors de l\'envoi',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper pour générer un rapport (exemple)
     */
    private function generateReport(): string
    {
        return view('emails.monthly-report', [
            'data' => [
                'revenue' => 150000,
                'orders' => 320,
                'customers' => 1200,
            ]
        ])->render();
    }

    /**
     * Helper pour générer un PDF (exemple)
     */
    private function generateReportPDF(): string
    {
        // Utilisez votre bibliothèque PDF préférée (TCPDF, etc.)
        return file_get_contents(storage_path('reports/example.pdf'));
    }
}
