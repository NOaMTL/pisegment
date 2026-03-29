<?php

declare(strict_types=1);

namespace App\Services\ExternalApi;

use App\Services\Http\HttpClientInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service pour appels API externes via CRON
 *
 * USE CASE:
 * - Cron régulier qui appelle une API externe
 * - Pas de refresh token : authentification complète à chaque expiration
 * - Proxy potentiellement lent → retry multiple
 * - Stockage du token en cache pour éviter auth répétées
 *
 * CONFIGURATION (.env):
 * EXTERNAL_API_BASE_URI=https://api.external.com
 * EXTERNAL_API_CLIENT_ID=your_client_id
 * EXTERNAL_API_CLIENT_SECRET=your_client_secret
 * EXTERNAL_API_PROXY=http://proxy.company.com:8080  # Optionnel
 *
 * USAGE DANS VOTRE CRON:
 * $api = app(CronApiService::class);
 * $data = $api->fetchData();
 */
class CronApiService
{
    private const CACHE_TOKEN_KEY = 'cron_api_access_token';

    private const TOKEN_LIFETIME_MINUTES = 55; // Token expire généralement en 1h, on met 55min pour avoir une marge

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
        $this->configureClient();
    }

    /**
     * Configure le client HTTP pour proxy lent et retry multiple
     */
    private function configureClient(): void
    {
        $this->httpClient
            ->withBaseUri(config('services.external_api.base_uri'))
            ->withProxy(config('services.external_api.proxy'))
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            // Proxy lent : augmentez retries et timeouts
            ->setMaxRetries(5) // 5 tentatives au lieu de 3
            ->setTimeouts(90, 30) // 90s timeout, 30s connexion
            // Auto-refresh si token expiré
            ->onTokenExpired(fn () => $this->authenticateAndGetToken());
    }

    /**
     * Exemple: Récupérer des données depuis l'API
     *
     * Méthode que vous appellerez depuis votre commande Artisan CRON
     */
    public function fetchData(array $params = []): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->get('/api/data', [
                'query' => $params,
            ]);
    }

    /**
     * Exemple: Envoyer des données à l'API
     */
    public function sendData(array $data): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->post('/api/data', $data);
    }

    /**
     * Exemple: Récupérer un rapport
     */
    public function getReport(string $reportId): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->get("/api/reports/{$reportId}");
    }

    /**
     * Exemple: Synchroniser des entités
     */
    public function syncEntities(array $entities): array
    {
        return $this->httpClient
            ->withToken($this->getAccessToken())
            ->post('/api/sync', ['entities' => $entities]);
    }

    /**
     * Obtenir le token d'accès (depuis cache ou auth)
     *
     * Cette méthode vérifie d'abord le cache. Si pas de token,
     * elle fait une authentification complète.
     */
    private function getAccessToken(): string
    {
        // Vérifier le cache
        $token = Cache::get(self::CACHE_TOKEN_KEY);

        if ($token) {
            Log::debug('Token trouvé en cache');

            return $token;
        }

        // Pas de token en cache, on s'authentifie
        Log::info('Pas de token en cache, authentification...');

        return $this->authenticateAndGetToken();
    }

    /**
     * Authentification complète (pas de refresh token)
     *
     * Cette méthode est appelée:
     * 1. Si pas de token en cache
     * 2. Si le token est expiré (via onTokenExpired)
     *
     * @return string Le nouveau access token
     */
    private function authenticateAndGetToken(): string
    {
        Log::info('Authentification API externe en cours...');

        try {
            // Créer une nouvelle instance du client pour éviter récursion
            $authClient = app(HttpClientInterface::class);

            $response = $authClient
                ->withBaseUri(config('services.external_api.base_uri'))
                ->withProxy(config('services.external_api.proxy'))
                ->setMaxRetries(5) // Proxy lent
                ->setTimeouts(90, 30)
                ->post('/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('services.external_api.client_id'),
                    'client_secret' => config('services.external_api.client_secret'),
                ]);

            if (! isset($response['access_token'])) {
                Log::error('Authentification échouée: pas de access_token dans la réponse', [
                    'response' => $response,
                ]);

                throw new \RuntimeException('Authentication failed: no access_token in response');
            }

            $accessToken = $response['access_token'];

            // Stocker en cache avec expiration
            // Le token expire généralement en 1h, on met 55min pour avoir une marge
            Cache::put(
                self::CACHE_TOKEN_KEY,
                $accessToken,
                now()->addMinutes(self::TOKEN_LIFETIME_MINUTES)
            );

            Log::info('Authentification réussie, token stocké en cache', [
                'expires_in' => self::TOKEN_LIFETIME_MINUTES.' minutes',
            ]);

            return $accessToken;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'authentification API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Failed to authenticate with external API: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Forcer une nouvelle authentification (vider le cache)
     *
     * Utile pour le debugging ou forcer un refresh manuel
     */
    public function forceReauthenticate(): string
    {
        Cache::forget(self::CACHE_TOKEN_KEY);
        Log::info('Cache token vidé, réauthentification forcée');

        return $this->authenticateAndGetToken();
    }

    /**
     * Vérifier si un token valide existe en cache
     */
    public function hasValidToken(): bool
    {
        return Cache::has(self::CACHE_TOKEN_KEY);
    }

    /**
     * Obtenir les infos du token (pour debugging)
     */
    public function getTokenInfo(): array
    {
        $hasToken = Cache::has(self::CACHE_TOKEN_KEY);
        $token = Cache::get(self::CACHE_TOKEN_KEY);

        return [
            'has_token' => $hasToken,
            'token_preview' => $token ? substr($token, 0, 20).'...' : null,
            'cache_key' => self::CACHE_TOKEN_KEY,
            'lifetime_minutes' => self::TOKEN_LIFETIME_MINUTES,
        ];
    }
}
