<?php

namespace App\Http\Controllers;

use App\Services\Http\HttpClientInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Exemple d'utilisation du HttpClient service
 *
 * Démontre comment utiliser le service HTTP avec:
 * - Gestion du proxy
 * - Refresh token automatique sur 401
 * - Différentes méthodes HTTP
 */
class ExampleHttpClientController extends Controller
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    /**
     * Exemple 1: Requête simple GET
     */
    public function simpleGet()
    {
        try {
            $data = $this->httpClient
                ->withBaseUri('https://api.example.com')
                ->get('/users');

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exemple 2: POST avec authentification
     */
    public function authenticatedPost()
    {
        $token = Cache::get('api_access_token');

        try {
            $data = $this->httpClient
                ->withBaseUri('https://api.example.com')
                ->withToken($token)
                ->post('/users', [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ]);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exemple 3: Utilisation avec proxy
     */
    public function withProxy()
    {
        try {
            $data = $this->httpClient
                ->withBaseUri('https://api.example.com')
                ->withProxy('http://proxy.company.com:8080')
                ->get('/users');

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exemple 4: Avec refresh token automatique
     *
     * Le plus important: quand le serveur retourne 401,
     * le client va automatiquement appeler votre fonction de refresh
     * et réessayer la requête avec le nouveau token
     */
    public function withAutoRefreshToken()
    {
        $accessToken = Cache::get('api_access_token');
        $refreshToken = Cache::get('api_refresh_token');

        try {
            $data = $this->httpClient
                ->withBaseUri('https://api.example.com')
                ->withToken($accessToken)
                ->onTokenExpired(function () use ($refreshToken) {
                    // Cette fonction est appelée automatiquement quand vous recevez 401
                    return $this->refreshAccessToken($refreshToken);
                })
                ->get('/protected/data');

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exemple 5: Configuration complète avec headers personnalisés
     */
    public function fullConfiguration()
    {
        $refreshToken = Cache::get('api_refresh_token');

        try {
            $data = $this->httpClient
                ->withBaseUri('https://api.example.com')
                ->withToken(Cache::get('api_access_token'))
                ->withProxy(config('services.api.proxy')) // depuis config/services.php
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Client-ID' => config('services.api.client_id'),
                    'X-Custom-Header' => 'custom-value',
                ])
                ->onTokenExpired(fn () => $this->refreshAccessToken($refreshToken))
                ->post('/advanced/endpoint', [
                    'data' => 'some data',
                    'nested' => [
                        'key' => 'value',
                    ],
                ]);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exemple 6: PUT request
     */
    public function updateResource()
    {
        try {
            $data = $this->httpClient
                ->withBaseUri('https://api.example.com')
                ->withToken(Cache::get('api_access_token'))
                ->put('/users/123', [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                ]);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exemple 7: DELETE request
     */
    public function deleteResource()
    {
        try {
            $data = $this->httpClient
                ->withBaseUri('https://api.example.com')
                ->withToken(Cache::get('api_access_token'))
                ->delete('/users/123');

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exemple 8: Configuration pour proxy lent avec retry multiple
     *
     * Si votre proxy met du temps à répondre, augmentez les retries et timeouts
     */
    public function withSlowProxy()
    {
        $refreshToken = Cache::get('api_refresh_token');

        try {
            $data = $this->httpClient
                ->withBaseUri('https://api.example.com')
                ->withToken(Cache::get('api_access_token'))
                ->withProxy('http://proxy.company.com:8080')
                ->setMaxRetries(5) // 5 tentatives au lieu de 3
                ->setTimeouts(60, 20) // Timeout 60s, connexion 20s
                ->onTokenExpired(fn () => $this->refreshAccessToken($refreshToken))
                ->get('/data');

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exemple 9: API avec erreurs de token personnalisées
     *
     * Certaines APIs retournent 200 avec un code d'erreur dans le body
     * au lieu de 401. Configurez les indicateurs d'erreur personnalisés.
     */
    public function withCustomTokenErrors()
    {
        $refreshToken = Cache::get('api_refresh_token');

        try {
            $data = $this->httpClient
                ->withBaseUri('https://api.customapi.com')
                ->withToken(Cache::get('api_access_token'))
                ->setTokenErrorIndicators([
                    'TOKEN_EXPIRED',
                    'SESSION_EXPIRED',
                    'AUTH_FAILED',
                    'invalid_credentials',
                ])
                ->onTokenExpired(fn () => $this->refreshAccessToken($refreshToken))
                ->get('/custom/endpoint');

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exemple 10: Configuration complète pour environnement difficile
     *
     * Proxy lent + API avec erreurs personnalisées + timeout élevé
     */
    public function robustConfiguration()
    {
        $refreshToken = Cache::get('api_refresh_token');

        try {
            $data = $this->httpClient
                ->withBaseUri('https://api.example.com')
                ->withToken(Cache::get('api_access_token'))
                ->withProxy(config('services.api.proxy'))
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Client-ID' => config('services.api.client_id'),
                ])
                ->setMaxRetries(5) // Beaucoup de retries pour proxy lent
                ->setTimeouts(90, 30) // Timeouts généreux
                ->setTokenErrorIndicators([
                    'token_expired',
                    'invalid_token',
                    'SESSION_TIMEOUT',
                    'AUTH_ERROR',
                ])
                ->onTokenExpired(fn () => $this->refreshAccessToken($refreshToken))
                ->post('/important/data', [
                    'critical' => 'information',
                ]);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fonction de refresh token
     *
     * Cette fonction appelle l'API externe pour obtenir un nouveau access token
     * en utilisant le refresh token
     */
    private function refreshAccessToken(string $refreshToken): ?string
    {
        try {
            // Créez une nouvelle instance pour éviter la récursion
            $client = app(HttpClientInterface::class);

            $response = $client
                ->withBaseUri('https://api.example.com')
                ->post('/oauth/refresh', [
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                    'client_id' => config('services.api.client_id'),
                    'client_secret' => config('services.api.client_secret'),
                ]);

            if (isset($response['access_token'])) {
                $newAccessToken = $response['access_token'];

                // Stockez le nouveau token
                Cache::put('api_access_token', $newAccessToken, now()->addMinutes(55));

                // Si un nouveau refresh token est fourni, mettez-le à jour aussi
                if (isset($response['refresh_token'])) {
                    Cache::put('api_refresh_token', $response['refresh_token'], now()->addDays(30));
                }

                return $newAccessToken;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

/**
 * CONFIGURATION DANS config/services.php:
 *
 * return [
 *     'api' => [
 *         'base_uri' => env('API_BASE_URI', 'https://api.example.com'),
 *         'client_id' => env('API_CLIENT_ID'),
 *         'client_secret' => env('API_CLIENT_SECRET'),
 *         'proxy' => env('API_PROXY', null), // Ex: 'http://proxy.company.com:8080'
 *     ],
 * ];
 *
 * CONFIGURATION DANS .env:
 *
 * API_BASE_URI=https://api.example.com
 * API_CLIENT_ID=your_client_id
 * API_CLIENT_SECRET=your_client_secret
 * API_PROXY=http://proxy.company.com:8080
 *
 * EXEMPLE D'UTILISATION DANS UN JOB OU COMMAND:
 *
 * use App\Services\Http\HttpClientInterface;
 *
 * class SyncDataJob
 * {
 *     public function __construct(
 *         private HttpClientInterface $httpClient
 *     ) {}
 *
 *     public function handle()
 *     {
 *         $refreshToken = Cache::get('api_refresh_token');
 *
 *         $data = $this->httpClient
 *             ->withBaseUri(config('services.api.base_uri'))
 *             ->withToken(Cache::get('api_access_token'))
 *             ->withProxy(config('services.api.proxy'))
 *             ->onTokenExpired(function () use ($refreshToken) {
 *                 // Votre logique de refresh token
 *                 return $this->refreshToken($refreshToken);
 *             })
 *             ->get('/data');
 *
 *         // Traitez vos données...
 *     }
 * }
 *
 * CHANGER DE CLIENT HTTP:
 *
 * Pour utiliser un autre client HTTP (comme Symfony HttpClient):
 *
 * 1. Créez une nouvelle classe qui implémente HttpClientInterface
 *    Exemple: app/Services/Http/SymfonyHttpClient.php
 *
 * 2. Modifiez AppServiceProvider.php:
 *    $this->app->bind(HttpClientInterface::class, SymfonyHttpClient::class);
 *
 * 3. Tout votre code existant continue de fonctionner!
 */
