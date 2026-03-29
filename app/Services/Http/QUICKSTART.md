# Service HTTP Client - Guide de démarrage rapide

⚡ **Ce client est 100% indépendant de Laravel** - Seule dépendance : Guzzle  
📖 **[Usage PHP procédural sans Laravel →](STANDALONE.md)**

## 📁 Fichiers créés

### Core Service
- `app/Services/Http/HttpClientInterface.php` - Interface du service
- `app/Services/Http/GuzzleHttpClient.php` - Implémentation avec Guzzle
- `app/Services/Http/README.md` - Documentation complète

### Examples
- `app/Http/Controllers/ExampleHttpClientController.php` - 10 exemples d'utilisation
- `app/Services/ExternalApi/ExampleApiService.php` - Service API complet avec auto-refresh

### Configuration
- `app/Providers/AppServiceProvider.php` - Service enregistré ✅
- `config/services.example.php` - Configuration des APIs
- `.env.http-client.example` - Variables d'environnement

### Tests
- `tests/Unit/Services/HttpClientTest.php` - Tests unitaires

## 🚀 Démarrage rapide (3 étapes)

### 1. Configuration .env

Ajoutez ces lignes à votre `.env`:

```env
API_BASE_URI=https://api.example.com
API_CLIENT_ID=your_client_id
API_CLIENT_SECRET=your_client_secret
API_PROXY=  # Optionnel: http://proxy.company.com:8080
```

### 2. Configuration config/services.php

Ajoutez cette section:

```php
'api' => [
    'base_uri' => env('API_BASE_URI'),
    'client_id' => env('API_CLIENT_ID'),
    'client_secret' => env('API_CLIENT_SECRET'),
    'proxy' => env('API_PROXY', null),
],
```

### 3. Utilisation dans votre code

```php
use App\Services\Http\HttpClientInterface;

class YourController extends Controller
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function getData()
    {
        $data = $this->httpClient
            ->withBaseUri(config('services.api.base_uri'))
            ->withToken($accessToken)
            ->withProxy(config('services.api.proxy'))
            ->onTokenExpired(fn() => $this->refreshToken())
            ->get('/endpoint');
    }
}
```

## ✨ Fonctionnalités principales

### 🔐 Auto-refresh token sur 401

```php
$httpClient
    ->withToken($accessToken)
    ->onTokenExpired(function () use ($refreshToken) {
        // Appelé automatiquement sur 401
        return $this->getNewToken($refreshToken);
    })
    ->get('/protected/data');
```

### 🌐 Support du proxy

```php
// Sans authentification
$httpClient->withProxy('http://proxy.company.com:8080');

// Avec authentification
$httpClient->withProxy('http://user:password@proxy.company.com:8080');
```

### 🔄 Retry automatique intelligent

Le client détecte automatiquement les erreurs et retry intelligemment:

**Token expiré/invalide** (détecté automatiquement):
- HTTP 401 Unauthorized
- Ou `{"error": "token_expired"}` dans le body (même si HTTP 200/400)

**Erreurs réseau** (retry automatique):
- Timeout de connexion ou requête
- Erreurs proxy
- Erreurs SSL/certificat

**Configuration par défaut**:
- 3 retries maximum
- Backoff exponentiel: 1s → 2s → 4s → 8s (avec jitter aléatoire)

**Personnalisation pour proxy lent**:
```php
$httpClient
    ->withProxy('http://proxy.company.com:8080')
    ->setMaxRetries(5)        // Plus de retries
    ->setTimeouts(90, 30)     // Timeouts plus longs
    ->get('/data');
```

**APIs avec erreurs personnalisées**:
```php
$httpClient
    ->setTokenErrorIndicators([
        'TOKEN_EXPIRED',      // API custom
        'SESSION_TIMEOUT',
        'AUTH_FAILED',
    ])
    ->onTokenExpired(fn() => $this->refreshToken())
    ->get('/data');
```

### 📝 Headers personnalisés

```php
$httpClient->withHeaders([
    'Accept' => 'application/json',
    'X-Client-ID' => 'your-client-id',
    'X-Custom-Header' => 'value'
]);
```

## 📖 Documentation complète

- **README détaillé**: [app/Services/Http/README.md](app/Services/Http/README.md)
- **Exemples concrets**: [app/Http/Controllers/ExampleHttpClientController.php](app/Http/Controllers/ExampleHttpClientController.php)
- **Service API complet**: [app/Services/ExternalApi/ExampleApiService.php](app/Services/ExternalApi/ExampleApiService.php)

## 🧪 Tests

Exécuter les tests:

```bash
php artisan test --filter=HttpClientTest
```

## 🔄 Changer de client HTTP

Pour remplacer Guzzle par un autre client (Symfony, etc.):

1. Créez `app/Services/Http/YourHttpClient.php` qui implémente `HttpClientInterface`
2. Modifiez `AppServiceProvider.php`:
   ```php
   $this->app->bind(HttpClientInterface::class, YourHttpClient::class);
   ```
3. **Votre code existant continue de fonctionner!** ✨

## 📚 Exemples pratiques

### Exemple 1: Appel simple

```php
$data = $httpClient
    ->withBaseUri('https://api.example.com')
    ->get('/users');
```

### Exemple 2: POST avec authentification

```php
$data = $httpClient
    ->withBaseUri('https://api.example.com')
    ->withToken($token)
    ->post('/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
```

### Exemple 3: Configuration complète

```php
$data = $httpClient
    ->withBaseUri('https://api.example.com')
    ->withToken($accessToken)
    ->withProxy('http://proxy.company.com:8080')
    ->withHeaders(['X-Client-ID' => 'abc123'])
    ->onTokenExpired(fn() => $this->refreshToken())
    ->get('/protected/data');
```

### Exemple 4: Service API dédié (RECOMMANDÉ)

```php
use App\Services\ExternalApi\ExampleApiService;

class UserController extends Controller
{
    public function __construct(
        private ExampleApiService $api
    ) {}

    public function index()
    {
        // Tout est géré automatiquement: token, refresh, proxy, etc.
        $users = $this->api->getUsers();
        
        return view('users.index', ['users' => $users]);
    }
}
```

## 🎯 Prochaines étapes

1. ✅ Copiez `.env.http-client.example` → `.env` (ajoutez vos credentials)
2. ✅ Copiez `config/services.example.php` → `config/services.php` (ajoutez votre config)
3. ✅ Créez votre propre service API en vous basant sur `ExampleApiService.php`
4. ✅ Testez avec `ExampleHttpClientController.php`
5. ✅ Ajoutez vos tests dans `tests/Unit/Services/`

## 💡 Conseils

- **Production**: Utilisez `singleton()` pour une seule instance
- **Cache**: Stockez les tokens dans le cache (Redis/Memcached)
- **Logs**: Les erreurs sont automatiquement loguées
- **Tests**: Mockez l'interface pour vos tests unitaires
- **Proxy**: Configurez par environnement (dev sans, prod avec)

## 🆘 Support

- Documentation complète: [app/Services/Http/README.md](app/Services/Http/README.md)
- Exemples: [app/Http/Controllers/ExampleHttpClientController.php](app/Http/Controllers/ExampleHttpClientController.php)
- Tests: [tests/Unit/Services/HttpClientTest.php](tests/Unit/Services/HttpClientTest.php)

## ⚙️ Configuration avancée

Voir `config/services.example.php` pour:
- Multiple APIs configuration
- Timeouts personnalisés
- Max retries
- Proxy par API

---

**Tout est prêt à l'emploi!** 🎉
