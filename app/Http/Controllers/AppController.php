<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $id): Response
    {
        // Validation du format du code solution (5 caractères alphanumériques)
        if (! preg_match('/^[A-Z0-9]{5}$/', strtoupper($id))) {
            abort(404, 'Code solution invalide');
        }

        // TODO: Remplacer par un vrai appel API
        // $appData = Http::get("https://api.example.com/apps/{$id}")->json();

        // Données mockées pour le développement
        $appData = [
            'code_solution' => strtoupper($id),
            'name' => 'Application '.strtoupper($id),
            'description' => 'Description de l\'application avec le code solution '.strtoupper($id),
            'status' => 'active',
            'created_at' => now()->subDays(rand(1, 365))->format('d/m/Y H:i'),
            'last_updated' => now()->subDays(rand(0, 30))->format('d/m/Y H:i'),
            'version' => '1.'.rand(0, 9).'.'.rand(0, 99),
            'owner' => [
                'name' => 'Propriétaire '.strtoupper($id),
                'email' => 'owner.'.strtolower($id).'@example.com',
            ],
            'stats' => [
                'total_users' => rand(100, 10000),
                'active_users' => rand(50, 5000),
                'api_calls_today' => rand(1000, 50000),
                'uptime_percentage' => rand(95, 100),
            ],
            'logs' => array_map(fn ($i) => [
                'id' => $i,
                'level' => ['info', 'warning', 'error', 'debug'][rand(0, 3)],
                'message' => [
                    'Connexion utilisateur réussie',
                    'Erreur de validation des données',
                    'Mise à jour de configuration',
                    'Traitement batch terminé',
                    'Exception non gérée détectée',
                    'Cache vidé avec succès',
                    'Nouvelle session créée',
                    'Timeout de connexion base de données',
                    'Upload de fichier réussi',
                    'Requête API externe échouée',
                ][rand(0, 9)],
                'timestamp' => now()->subMinutes(rand(1, 7200))->format('d/m/Y H:i:s'),
                'user' => 'user'.rand(1, 100),
            ], range(1, 15)),
            'api_calls' => array_map(fn ($i) => [
                'id' => $i,
                'method' => ['GET', 'POST', 'PUT', 'DELETE'][rand(0, 3)],
                'endpoint' => [
                    '/api/users',
                    '/api/orders',
                    '/api/products',
                    '/api/customers',
                    '/api/reports',
                    '/api/analytics',
                    '/api/settings',
                    '/api/notifications',
                ][rand(0, 7)],
                'status' => [200, 200, 200, 201, 400, 404, 500][rand(0, 6)],
                'duration' => rand(50, 2000).'ms',
                'timestamp' => now()->subMinutes(rand(1, 7200))->format('d/m/Y H:i:s'),
            ], range(1, 15)),
            'connections' => array_map(function ($daysAgo) {
                return [
                    'date' => now()->subDays($daysAgo)->format('Y-m-d'),
                    'count' => rand(0, 250),
                ];
            }, range(29, 0)),
        ];

        return Inertia::render('App', [
            'app' => $appData,
        ]);
    }
}
