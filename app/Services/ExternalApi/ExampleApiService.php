<?php

declare(strict_types=1);

namespace App\Services\ExternalApi;

use App\Services\Http\HttpClientInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Service pour gérer les appels à une API externe
 *
 * Exemple d'encapsulation du HttpClient dans un service métier
 * Cette classe masque la complexité de l'API et fournit des méthodes
 * métier simples à utiliser dans votre application.
 *
 * Usage:
 * $api = app(ExampleApiService::class);
 * $users = $api->getUsers();
 * $user = $api->createUser(['name' => 'John', 'email' => 'john@example.com']);
 */
class ExampleApiService
{
    private const CACHE_TOKEN_KEY = 'example_api_access_token';

    private const CACHE_REFRESH_TOKEN_KEY = 'example_api_refresh_token';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
        $this->configureClient();
    }

    /**
     * Configure le client HTTP avec les paramètres de l'API
     */
    private function configureClient(): void
    {
        $this->httpClient
            ->withBaseUri(config('services.api.base_uri'))
            ->withProxy(config('services.api.proxy'))
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Client-ID' => config('services.api.client_id'),
            ])
            ->onTokenExpired(fn () => $this->refreshAccessToken());
    }

    /**
     * Obtenir la liste des utilisateurs
     */
    public function getUsers(int $page = 1, int $limit = 10): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->get('/users', [
                'query' => [
                    'page' => $page,
                    'limit' => $limit,
                ],
            ]);
    }

    /**
     * Obtenir un utilisateur par ID
     */
    public function getUser(int $userId): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->get("/users/{$userId}");
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function createUser(array $data): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->post('/users', $data);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser(int $userId, array $data): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->put("/users/{$userId}", $data);
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser(int $userId): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->delete("/users/{$userId}");
    }

    /**
     * Rechercher des utilisateurs
     */
    public function searchUsers(string $query): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->get('/users/search', [
                'query' => ['q' => $query],
            ]);
    }

    /**
     * Obtenir des statistiques
     */
    public function getStatistics(string $startDate, string $endDate): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->get('/statistics', [
                'query' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ]);
    }

    /**
     * Uploader un fichier
     */
    public function uploadFile(string $filePath, string $type = 'document'): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->post('/files/upload', [], [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                    ],
                    [
                        'name' => 'type',
                        'contents' => $type,
                    ],
                ],
            ]);
    }

    /**
     * Obtenir le token d'accès depuis le cache
     */
    private function getAccessToken(): string
    {
        $token = Cache::get(self::CACHE_TOKEN_KEY);

        if (! $token) {
            // Si pas de token en cache, on fait la première authentification
            $this->authenticate();
            $token = Cache::get(self::CACHE_TOKEN_KEY);
        }

        return $token;
    }

    /**
     * Authentification initiale pour obtenir les tokens
     */
    private function authenticate(): void
    {
        $response = $this->httpClient
            ->post('/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.api.client_id'),
                'client_secret' => config('services.api.client_secret'),
            ]);

        if (isset($response['access_token'])) {
            // Stocker le token avec une durée de 55 minutes (généralement les tokens expirent en 1h)
            Cache::put(
                self::CACHE_TOKEN_KEY,
                $response['access_token'],
                now()->addMinutes(55)
            );

            if (isset($response['refresh_token'])) {
                Cache::put(
                    self::CACHE_REFRESH_TOKEN_KEY,
                    $response['refresh_token'],
                    now()->addDays(30)
                );
            }
        }
    }

    /**
     * Rafraîchir le token d'accès
     *
     * Cette méthode est appelée automatiquement par le HttpClient
     * quand une requête retourne 401
     */
    private function refreshAccessToken(): ?string
    {
        $refreshToken = Cache::get(self::CACHE_REFRESH_TOKEN_KEY);

        if (! $refreshToken) {
            // Si pas de refresh token, on réauthentifie
            $this->authenticate();

            return Cache::get(self::CACHE_TOKEN_KEY);
        }

        try {
            // Créer une nouvelle instance pour éviter la récursion
            $client = app(HttpClientInterface::class);

            $response = $client
                ->withBaseUri(config('services.api.base_uri'))
                ->post('/oauth/refresh', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => config('services.api.client_id'),
                    'client_secret' => config('services.api.client_secret'),
                ]);

            if (isset($response['access_token'])) {
                $newToken = $response['access_token'];

                // Stocker le nouveau token
                Cache::put(
                    self::CACHE_TOKEN_KEY,
                    $newToken,
                    now()->addMinutes(55)
                );

                // Mettre à jour le refresh token s'il est fourni
                if (isset($response['refresh_token'])) {
                    Cache::put(
                        self::CACHE_REFRESH_TOKEN_KEY,
                        $response['refresh_token'],
                        now()->addDays(30)
                    );
                }

                return $newToken;
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
            ]);

            // En cas d'échec, on réessaie une authentification complète
            $this->authenticate();

            return Cache::get(self::CACHE_TOKEN_KEY);
        }
    }

    /**
     * Vider le cache des tokens (utile pour forcer une nouvelle authentification)
     */
    public function clearTokens(): void
    {
        Cache::forget(self::CACHE_TOKEN_KEY);
        Cache::forget(self::CACHE_REFRESH_TOKEN_KEY);
    }

    /**
     * Vérifier si le service est authentifié
     */
    public function isAuthenticated(): bool
    {
        return Cache::has(self::CACHE_TOKEN_KEY);
    }
}

/**
 * UTILISATION DANS VOS CONTRÔLEURS/JOBS/COMMANDS:
 *
 * use App\Services\ExternalApi\ExampleApiService;
 *
 * class UserController extends Controller
 * {
 *     public function __construct(
 *         private ExampleApiService $apiService
 *     ) {}
 *
 *     public function index()
 *     {
 *         $users = $this->apiService->getUsers(page: 1, limit: 20);
 *         return view('users.index', ['users' => $users]);
 *     }
 *
 *     public function store(Request $request)
 *     {
 *         $user = $this->apiService->createUser([
 *             'name' => $request->name,
 *             'email' => $request->email,
 *         ]);
 *
 *         return redirect()->route('users.show', $user['id']);
 *     }
 * }
 *
 * AVANTAGES DE CETTE APPROCHE:
 *
 * 1. Encapsulation: Toute la complexité de l'API est dans une seule classe
 * 2. Réutilisabilité: Utilisez le service partout dans votre app
 * 3. Testabilité: Facile à mocker dans les tests
 * 4. Maintenabilité: Un seul endroit à modifier si l'API change
 * 5. Type-safety: Les méthodes ont des signatures claires
 * 6. Auto-refresh: Le token est géré automatiquement
 * 7. Cache: Les tokens sont mis en cache pour les performances
 *
 * ENREGISTREMENT DANS LE SERVICE CONTAINER (optionnel):
 *
 * Dans AppServiceProvider.php:
 *
 * public function register(): void
 * {
 *     $this->app->singleton(ExampleApiService::class);
 * }
 *
 * Cela créera une seule instance du service pour toute l'application.
 */
