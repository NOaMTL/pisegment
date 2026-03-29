# Service HTTP Client (Guzzle)

Service HTTP abstrait avec support du proxy et refresh token automatique pour les APIs externes.

## ⚡ Indépendance Laravel

**Ce client est 100% indépendant de Laravel** et peut être copié-collé dans n'importe quel projet PHP (procédural, Symfony, etc.).

- ✅ **Seule dépendance** : Guzzle (librairie HTTP standard PHP)
- ✅ **Pas de dépendance** : Laravel, Illuminate, Facades
- ✅ **Logging optionnel** : Utilise `error_log()` natif PHP par défaut
- ✅ **Portable** : Ajustez le namespace et c'est prêt

📖 **[Guide d'utilisation Standalone (PHP procédural) →](STANDALONE.md)**

## Structure

```
app/Services/Http/
├── HttpClientInterface.php      # Interface définissant le contrat
├── GuzzleHttpClient.php         # Implémentation avec Guzzle (standalone)
├── README.md                    # Documentation complète (Laravel)
├── QUICKSTART.md                # Guide rapide (Laravel)
└── STANDALONE.md                # Usage PHP procédural (sans Laravel)
```

## Fonctionnalités

✅ **Proxy Support** : Configuration du proxy pour requêtes sortantes  
✅ **Auto Retry Intelligent** : Détection des tokens expirés dans HTTP status ET body de réponse  
✅ **Retry Réseau** : Retry automatique sur timeout, connexion, proxy (3 tentatives par défaut)  
✅ **Token Management** : Injection automatique du Bearer token + refresh automatique  
✅ **Backoff Exponentiel** : Délai croissant entre retries (1s, 2s, 4s, 8s) avec jitter  
✅ **Middleware** : Stack de middleware Guzzle personnalisable  
✅ **Error Handling** : Gestion des erreurs avec logging  
✅ **Flexible** : Facilement remplaçable par un autre client HTTP

## Installation

Guzzle est déjà inclus dans Laravel. Si nécessaire:

```bash
composer require guzzlehttp/guzzle
```

## Utilisation

### Injection de dépendances (recommandé)

```php
use App\Services\Http\HttpClientInterface;

class YourController extends Controller
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function fetchData()
    {
        $data = $this->httpClient
            ->withBaseUri('https://api.example.com')
            ->withToken($accessToken)
            ->get('/users');
    }
}
```

### Via le service container

```php
$httpClient = app(HttpClientInterface::class);
$data = $httpClient->get('https://api.example.com/users');
```

## Méthodes HTTP

### GET Request

```php
$data = $httpClient
    ->withBaseUri('https://api.example.com')
    ->get('/users', [
        'query' => ['page' => 1, 'limit' => 10],
        'headers' => ['Accept' => 'application/json']
    ]);
```

### POST Request

```php
$data = $httpClient
    ->withBaseUri('https://api.example.com')
    ->post('/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
```

### PUT Request

```php
$data = $httpClient
    ->withBaseUri('https://api.example.com')
    ->put('/users/123', [
        'name' => 'Jane Doe'
    ]);
```

### PATCH Request

```php
$data = $httpClient
    ->withBaseUri('https://api.example.com')
    ->patch('/users/123', [
        'email' => 'newemail@example.com'
    ]);
```

### DELETE Request

```php
$data = $httpClient
    ->withBaseUri('https://api.example.com')
    ->delete('/users/123');
```

## Configuration

### Base URI

```php
$httpClient->withBaseUri('https://api.example.com');
```

### Authentication Token

```php
$httpClient->withToken($accessToken);
```

### Custom Headers

```php
$httpClient->withHeaders([
    'Accept' => 'application/json',
    'X-Client-ID' => 'your-client-id',
    'X-Custom-Header' => 'value'
]);
```

### Proxy Configuration

```php
// HTTP proxy
$httpClient->withProxy('http://proxy.company.com:8080');

// HTTPS proxy avec authentification
$httpClient->withProxy('http://user:password@proxy.company.com:8080');

// Désactiver le proxy
$httpClient->withProxy(null);
```

## Refresh Token Automatique

Le plus important : gestion automatique du refresh token quand l'API retourne 401.

### Exemple complet

```php
use Illuminate\Support\Facades\Cache;

$accessToken = Cache::get('api_access_token');
$refreshToken = Cache::get('api_refresh_token');

$data = $httpClient
    ->withBaseUri('https://api.example.com')
    ->withToken($accessToken)
    ->onTokenExpired(function () use ($refreshToken) {
        // Cette fonction est appelée automatiquement sur 401
        return $this->refreshAccessToken($refreshToken);
    })
    ->get('/protected/resource');

// Fonction de refresh token
private function refreshAccessToken(string $refreshToken): ?string
{
    try {
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
            $newToken = $response['access_token'];
            
            // Stockez le nouveau token
            Cache::put('api_access_token', $newToken, now()->addMinutes(55));
            
            // Nouveau refresh token si fourni
            if (isset($response['refresh_token'])) {
                Cache::put('api_refresh_token', $response['refresh_token'], now()->addDays(30));
            }
            
            return $newToken;
        }
        
        return null;
    } catch (\Exception $e) {
        \Log::error('Token refresh failed', ['error' => $e->getMessage()]);
        return null;
    }
}
```

### Comment ça marche ?

1. Vous faites une requête avec un access token
2. Si l'API retourne **401 Unauthorized**
3. Le client appelle automatiquement votre fonction `onTokenExpired()`
4. Votre fonction refresh le token et retourne le nouveau
5. Le client **réessaie automatiquement** la requête avec le nouveau token
6. Tout est transparent pour votre code !

## Configuration complète

### config/services.php

```php
return [
    'api' => [
        'base_uri' => env('API_BASE_URI', 'https://api.example.com'),
        'client_id' => env('API_CLIENT_ID'),
        'client_secret' => env('API_CLIENT_SECRET'),
        'proxy' => env('API_PROXY', null),
        'timeout' => env('API_TIMEOUT', 30),
    ],
];
```

### .env

```env
API_BASE_URI=https://api.example.com
API_CLIENT_ID=your_client_id
API_CLIENT_SECRET=your_client_secret
API_PROXY=http://proxy.company.com:8080
API_TIMEOUT=30
```

## Utilisation dans un Job

```php
use App\Services\Http\HttpClientInterface;
use Illuminate\Support\Facades\Cache;

class SyncExternalDataJob implements ShouldQueue
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function handle()
    {
        $data = $this->httpClient
            ->withBaseUri(config('services.api.base_uri'))
            ->withToken(Cache::get('api_access_token'))
            ->withProxy(config('services.api.proxy'))
            ->onTokenExpired(fn () => $this->refreshToken())
            ->get('/data');

        // Traitez vos données...
    }

    private function refreshToken(): ?string
    {
        // Votre logique de refresh
    }
}
```

## Gestion des erreurs

Le client HTTP logue automatiquement les erreurs:

```php
try {
    $data = $httpClient->get('/endpoint');
} catch (\RuntimeException $e) {
    // Erreur HTTP (4xx, 5xx)
    Log::error('API call failed: ' . $e->getMessage());
}
```

Les logs incluent:
- Méthode HTTP
- URL
- Status code
- Response body
- Erreur message

## Responses

### Accéder à la dernière réponse brute

```php
$data = $httpClient->get('/users');
$response = $httpClient->getLastResponse();

// Psr\Http\Message\ResponseInterface
$statusCode = $response->getStatusCode();
$headers = $response->getHeaders();
$body = (string) $response->getBody();
```

## Retry Configuration

Le client dispose d'un système de retry intelligent qui gère automatiquement:
- ✅ **Erreurs de token** : Détecte les tokens expirés/invalides (HTTP 401 ou dans le body)
- ✅ **Erreurs réseau** : Retry automatique sur timeout, connexion, proxy
- ✅ **Backoff exponentiel** : Délai croissant entre retries avec jitter aléatoire

### Configuration par défaut

```php
// Par défaut (pas besoin de configurer):
maxRetries: 3
timeout: 30 secondes
connectTimeout: 10 secondes
backoff: 1s, 2s, 4s, 8s (exponentiel avec jitter 0-200ms)
```

### Détection d'erreur de token

Le client détecte automatiquement les tokens expirés/invalides de **2 façons** :

**1. Via le code HTTP 401 (standard)**
```http
HTTP/1.1 401 Unauthorized
```

**2. Via le body de la réponse (même si HTTP 200/400/403)**
```json
{
  "error": "token_expired",
  "message": "Your session has expired"
}
```

Indicateurs d'erreur détectés par défaut:
- `token_expired`
- `token_invalid`
- `invalid_token`
- `expired_token`
- `authentication_failed`
- `unauthorized`

Le client cherche ces mots-clés dans les champs: `error`, `error_code`, `code`, `error_type`, `type`, `message`, `error_message`.

### Personnaliser les indicateurs d'erreur

Si votre API utilise des codes d'erreur différents:

```php
$httpClient
    ->withBaseUri('https://api.custom.com')
    ->withToken($token)
    ->setTokenErrorIndicators([
        'TOKEN_EXPIRED',
        'SESSION_TIMEOUT',
        'AUTH_FAILED',
        'invalid_credentials',
    ])
    ->onTokenExpired(fn () => $this->refreshToken())
    ->get('/data');
```

### Augmenter les retries (proxy lent)

Pour les proxys lents ou réseaux instables:

```php
$httpClient
    ->withProxy('http://proxy.company.com:8080')
    ->setMaxRetries(5) // 5 tentatives au lieu de 3
    ->get('/data');
```

### Augmenter les timeouts

Pour les APIs qui mettent du temps à répondre:

```php
$httpClient
    ->setTimeouts(
        timeout: 90,        // Timeout global 90s
        connectTimeout: 30  // Timeout de connexion 30s
    )
    ->get('/slow-endpoint');
```

### Configuration complète pour environnement difficile

```php
$httpClient
    ->withBaseUri('https://api.example.com')
    ->withToken($token)
    ->withProxy('http://proxy.company.com:8080')
    ->setMaxRetries(5)
    ->setTimeouts(90, 30)
    ->setTokenErrorIndicators([
        'TOKEN_EXPIRED',
        'SESSION_TIMEOUT',
        'AUTH_ERROR',
    ])
    ->onTokenExpired(fn () => $this->refreshToken())
    ->get('/data');
```

### Erreurs qui déclenchent un retry automatique

**Erreurs de token** (déclenche `onTokenExpired()` puis retry):
- HTTP 401
- Body contenant un indicateur d'erreur configuré

**Erreurs réseau** (retry sans callback):
- Timeout de connexion
- Timeout de requête
- Erreur proxy
- Erreur SSL/certificat
- Erreur de connexion TCP

### Algorithme de backoff

Le délai entre chaque retry augmente exponentiellement:

```
Retry 1: 1 seconde  + jitter (0-200ms)
Retry 2: 2 secondes + jitter (0-200ms)
Retry 3: 4 secondes + jitter (0-200ms)
Retry 4: 8 secondes + jitter (0-200ms)
```

Le jitter aléatoire évite le "thundering herd" (beaucoup de clients qui retry en même temps).

## Logging (Optionnel & Portable)

Le client fonctionne **sans Laravel** grâce à un système de logging optionnel.

### Par défaut (error_log natif PHP)

Sans configuration, le client utilise `error_log()` natif PHP :

```php
$httpClient->get('/data');
// Écrit dans les logs PHP : [HttpClient][info] Token refreshed successfully
```

### Avec Laravel Log Facade

```php
$httpClient->setLogger(function($level, $message, $context) {
    \Log::$level($message, $context);
});
```

### Avec Monolog

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('http-client');
$logger->pushHandler(new StreamHandler(storage_path('logs/http-client.log')));

$httpClient->setLogger(function($level, $message, $context) use ($logger) {
    $logger->$level($message, $context);
});
```

### Logger personnalisé (fichier)

```php
$httpClient->setLogger(function($level, $message, $context) {
    $log = date('Y-m-d H:i:s') . " [$level] $message";
    if (!empty($context)) {
        $log .= ' | ' . json_encode($context);
    }
    file_put_contents(__DIR__ . '/http-client.log', $log . "\n", FILE_APPEND);
});
```

### Désactiver complètement le logging

```php
$httpClient->setLogger(function($level, $message, $context) {
    // Ne rien faire
});
```

### Messages loggés

Le client log automatiquement :

- `[info]` : Token refresh réussi, détection d'erreur de token
- `[warning]` : Max retries atteint, erreur réseau détectée
- `[error]` : Échec de refresh token, erreur HTTP

## Changer de client HTTP

Pour utiliser un autre client (Symfony HttpClient, etc.):

### 1. Créer une nouvelle implémentation

```php
// app/Services/Http/SymfonyHttpClient.php
namespace App\Services\Http;

use Symfony\Contracts\HttpClient\HttpClientInterface as SymfonyClient;

class SymfonyHttpClient implements HttpClientInterface
{
    public function __construct(
        private SymfonyClient $client
    ) {}

    public function get(string $url, array $options = []): array
    {
        $response = $this->client->request('GET', $url, $options);
        return $response->toArray();
    }

    // Implémentez les autres méthodes...
}
```

### 2. Modifier AppServiceProvider

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(HttpClientInterface::class, SymfonyHttpClient::class);
}
```

**Votre code existant continue de fonctionner sans modification!**

## Tests

### Exemple de test

```php
use App\Services\Http\HttpClientInterface;
use Illuminate\Support\Facades\Cache;

class ExternalApiTest extends TestCase
{
    public function test_can_fetch_data_with_token_refresh()
    {
        Cache::put('api_access_token', 'expired_token');
        Cache::put('api_refresh_token', 'refresh_token');

        $httpClient = app(HttpClientInterface::class);

        $data = $httpClient
            ->withBaseUri('https://api.example.com')
            ->withToken(Cache::get('api_access_token'))
            ->onTokenExpired(function () {
                // Mock token refresh
                return 'new_access_token';
            })
            ->get('/protected');

        $this->assertIsArray($data);
    }
}
```

### Mock le service dans les tests

```php
$mockClient = Mockery::mock(HttpClientInterface::class);
$mockClient->shouldReceive('get')
    ->once()
    ->andReturn(['data' => 'mocked']);

$this->app->instance(HttpClientInterface::class, $mockClient);
```

## Exemples avancés

Voir `app/Http/Controllers/ExampleHttpClientController.php` pour des exemples complets.

## Dépannage

### Le proxy ne fonctionne pas

Vérifiez la syntaxe:
```php
// ✅ Correct
->withProxy('http://proxy.example.com:8080')

// ❌ Incorrect
->withProxy('proxy.example.com:8080')
```

### Le refresh token ne fonctionne pas

- Vérifiez que votre callback retourne bien le nouveau token (string)
- Vérifiez les logs pour voir les erreurs
- Assurez-vous de ne pas créer de boucle infinie

### Timeouts

Modifiez le timeout dans le constructeur de GuzzleHttpClient:

```php
$config = [
    'timeout' => 60,          // Timeout total
    'connect_timeout' => 10,  // Timeout de connexion
];
```

## Ressources

- [Guzzle Documentation](http://docs.guzzlephp.org/)
- [PSR-7 HTTP Message](https://www.php-fig.org/psr/psr-7/)
- [Middleware Guzzle](http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html)
