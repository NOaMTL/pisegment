# Client HTTP - Usage Standalone (PHP Procédural)

Ce client HTTP est **100% indépendant de Laravel** et peut être utilisé dans n'importe quel projet PHP procédural.

## Dépendances

**Uniquement Guzzle** (aucune dépendance Laravel) :

```bash
composer require guzzlehttp/guzzle
```

## 📁 Fichiers à copier

Copiez seulement ces 2 fichiers dans votre projet PHP :

```
votre-projet/
├── HttpClientInterface.php      # Interface
└── GuzzleHttpClient.php         # Implémentation
```

**Modifications nécessaires** :

```php
// AVANT (Laravel avec namespace)
namespace App\Services\Http;

// APRÈS (votre namespace ou pas de namespace)
namespace VotreProjet\Http;
// OU supprimez complètement si pas d'autoloader
```

## 🚀 Utilisation en PHP Procédural

### 1. Sans autoloader (require manuel)

```php
<?php
require_once __DIR__ . '/vendor/autoload.php'; // Guzzle
require_once __DIR__ . '/HttpClientInterface.php';
require_once __DIR__ . '/GuzzleHttpClient.php';

$httpClient = new GuzzleHttpClient();

// Configuration
$httpClient->withBaseUri('https://api.example.com');
$httpClient->withToken('votre_access_token');

// Requête simple
$data = $httpClient->get('/users');
print_r($data);
```

### 2. Avec autoloader (recommandé)

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use VotreProjet\Http\GuzzleHttpClient;

$httpClient = new GuzzleHttpClient();
$data = $httpClient
    ->withBaseUri('https://api.example.com')
    ->withToken('votre_token')
    ->get('/users');
```

## 🔧 Configuration Complète

### Proxy

```php
$httpClient->withProxy('http://proxy.company.com:8080');

// Avec authentification
$httpClient->withProxy('http://user:password@proxy.company.com:8080');
```

### Retry et Timeouts

```php
$httpClient
    ->setMaxRetries(5)              // 5 tentatives
    ->setTimeouts(90, 30);          // 90s timeout, 30s connexion
```

### Token Auto-Refresh

```php
$accessToken = 'old_token';
$refreshToken = 'refresh_token';

$httpClient
    ->withToken($accessToken)
    ->onTokenExpired(function() use (&$accessToken, $refreshToken) {
        // Cette fonction sera appelée automatiquement sur erreur token
        $newToken = refreshAccessToken($refreshToken);
        $accessToken = $newToken; // Update la variable
        return $newToken;
    })
    ->get('/protected/data');

// Fonction de refresh
function refreshAccessToken($refreshToken) {
    $ch = curl_init('https://api.example.com/oauth/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'grant_type' => 'refresh_token',
        'refresh_token' => $refreshToken,
        'client_id' => 'your_client_id',
        'client_secret' => 'your_client_secret',
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}
```

### Erreurs de Token Personnalisées

```php
$httpClient
    ->setTokenErrorIndicators([
        'TOKEN_EXPIRED',
        'SESSION_TIMEOUT',
        'INVALID_CREDENTIALS',
    ])
    ->onTokenExpired(function() {
        return getNewToken();
    });
```

## 📊 Logging

### Par défaut (error_log natif PHP)

Sans configuration, le client utilise `error_log()` :

```php
$httpClient->get('/data');
// Logs dans php_error.log : [HttpClient][info] Token refreshed successfully
```

### Logger personnalisé

```php
// Simple file log
$httpClient->setLogger(function($level, $message, $context) {
    $log = date('Y-m-d H:i:s') . " [$level] $message";
    if (!empty($context)) {
        $log .= ' | ' . json_encode($context);
    }
    file_put_contents(__DIR__ . '/http-client.log', $log . "\n", FILE_APPEND);
});

// Ou avec Monolog
$logger = new \Monolog\Logger('http-client');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/app.log'));

$httpClient->setLogger(function($level, $message, $context) use ($logger) {
    $logger->$level($message, $context);
});

// Ou avec Laravel (si disponible)
$httpClient->setLogger(function($level, $message, $context) {
    \Log::$level($message, $context);
});
```

### Désactiver complètement le logging

```php
$httpClient->setLogger(function($level, $message, $context) {
    // Ne rien faire
});
```

## 💡 Exemple Complet PHP Procédural

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/GuzzleHttpClient.php';

// Configuration
$apiBaseUrl = 'https://api.external.com';
$clientId = 'your_client_id';
$clientSecret = 'your_client_secret';
$proxy = 'http://proxy.company.com:8080'; // null si pas de proxy

// Token storage (en production, utilisez une base de données)
$tokenFile = __DIR__ . '/tokens.json';

function getTokens() {
    global $tokenFile;
    if (file_exists($tokenFile)) {
        return json_decode(file_get_contents($tokenFile), true);
    }
    return ['access_token' => null, 'refresh_token' => null];
}

function saveTokens($accessToken, $refreshToken) {
    global $tokenFile;
    file_put_contents($tokenFile, json_encode([
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
    ]));
}

function authenticateApi($clientId, $clientSecret) {
    global $apiBaseUrl;
    $ch = curl_init("$apiBaseUrl/oauth/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function refreshToken($refreshToken, $clientId, $clientSecret) {
    global $apiBaseUrl;
    $ch = curl_init("$apiBaseUrl/oauth/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'grant_type' => 'refresh_token',
        'refresh_token' => $refreshToken,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Récupérer ou créer les tokens
$tokens = getTokens();
if (empty($tokens['access_token'])) {
    echo "Authentification initiale...\n";
    $authResult = authenticateApi($clientId, $clientSecret);
    $tokens['access_token'] = $authResult['access_token'];
    $tokens['refresh_token'] = $authResult['refresh_token'];
    saveTokens($tokens['access_token'], $tokens['refresh_token']);
}

// Créer le client HTTP
$httpClient = new GuzzleHttpClient();

// Configuration du logging
$httpClient->setLogger(function($level, $message, $context) {
    echo "[" . strtoupper($level) . "] $message\n";
    if (!empty($context)) {
        echo "  Context: " . json_encode($context) . "\n";
    }
});

// Configuration du client
$httpClient
    ->withBaseUri($apiBaseUrl)
    ->withToken($tokens['access_token'])
    ->withProxy($proxy)
    ->setMaxRetries(5)
    ->setTimeouts(90, 30)
    ->onTokenExpired(function() use ($tokens, $clientId, $clientSecret) {
        echo "Token expiré, refresh en cours...\n";
        $result = refreshToken($tokens['refresh_token'], $clientId, $clientSecret);
        
        if (isset($result['access_token'])) {
            saveTokens($result['access_token'], $result['refresh_token'] ?? $tokens['refresh_token']);
            return $result['access_token'];
        }
        
        return null;
    });

// Utilisation
try {
    echo "Récupération des utilisateurs...\n";
    $users = $httpClient->get('/users', [
        'query' => ['page' => 1, 'limit' => 10]
    ]);
    
    echo "Utilisateurs récupérés : " . count($users) . "\n";
    print_r($users);
    
    // POST
    echo "\nCréation d'un utilisateur...\n";
    $newUser = $httpClient->post('/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    echo "Utilisateur créé avec ID : " . $newUser['id'] . "\n";
    
} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}
```

## ⚙️ Configuration Avancée

### Désactiver la vérification SSL (développement seulement)

```php
// Modifiez dans GuzzleHttpClient.php ligne ~75
$config = [
    // ...
    'verify' => false, // Désactive la vérification SSL (DANGER en production)
];
```

### Headers personnalisés

```php
$httpClient->withHeaders([
    'X-API-Key' => 'your-api-key',
    'Accept' => 'application/json',
    'User-Agent' => 'MyApp/1.0',
]);
```

### Réponse brute (PSR-7)

```php
$httpClient->get('/data');
$lastResponse = $httpClient->getLastResponse();

// Psr\Http\Message\ResponseInterface
$statusCode = $lastResponse->getStatusCode();
$headers = $lastResponse->getHeaders();
$body = (string) $lastResponse->getBody();
```

## 🔒 Sécurité

1. **Ne pas hardcoder les credentials** : Utilisez variables d'environnement ou fichiers de config
2. **HTTPS uniquement** : Vérifiez que toutes les URLs utilisent HTTPS
3. **Stockage sécurisé des tokens** : Base de données chiffrée, pas de fichiers en production
4. **Proxy avec authentification** : Utilisez `http://user:password@proxy:port`
5. **Timeouts** : Configurez des timeouts raisonnables pour éviter les blocages

## 📝 Notes

- Le client fonctionne **sans Laravel, Symfony ou tout autre framework**
- Seule dépendance : **Guzzle** (librairie HTTP standard PHP)
- Compatible PHP 8.0+
- Logging natif avec `error_log()` par défaut
- Retry automatique sur erreurs réseau et token
- Backoff exponentiel : 1s, 2s, 4s, 8s...

## 🆘 Troubleshooting

### "Class 'GuzzleHttpClient' not found"

Solution : Ajustez le namespace ou utilisez `require_once`

### "SSL certificate problem"

Solution : Mettez à jour les certificats CA ou utilisez `'verify' => false` (dev uniquement)

### Pas de logs visibles

Solution : Vérifiez `php.ini` → `error_log` ou utilisez `setLogger()` personnalisé

### Proxy timeout

Solution : Augmentez les timeouts et retries :
```php
$httpClient->setMaxRetries(10)->setTimeouts(120, 60);
```
