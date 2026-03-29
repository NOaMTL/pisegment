<?php
/**
 * Exemple d'utilisation de GuzzleHttpClient en PHP PROCÉDURAL (sans Laravel)
 * 
 * Ce fichier montre comment utiliser le client HTTP dans un projet PHP standard.
 * 
 * Prérequis:
 * - composer require guzzlehttp/guzzle
 * - Copiez GuzzleHttpClient.php et HttpClientInterface.php
 */

require_once __DIR__ . '/../../../vendor/autoload.php'; // Guzzle via Composer

// Si vous n'utilisez pas l'autoloader Laravel, incluez manuellement:
// require_once __DIR__ . '/HttpClientInterface.php';
// require_once __DIR__ . '/GuzzleHttpClient.php';

use App\Services\Http\GuzzleHttpClient;

// ========================================
// EXEMPLE 1 : Requête GET simple
// ========================================
echo "=== EXEMPLE 1 : GET simple ===\n";

$client = new GuzzleHttpClient();

try {
    $data = $client
        ->withBaseUri('https://jsonplaceholder.typicode.com')
        ->get('/posts/1');
    
    echo "Post récupéré: {$data['title']}\n\n";
} catch (Exception $e) {
    echo "Erreur: {$e->getMessage()}\n\n";
}

// ========================================
// EXEMPLE 2 : POST avec authentification
// ========================================
echo "=== EXEMPLE 2 : POST avec token ===\n";

$client2 = new GuzzleHttpClient();

try {
    $result = $client2
        ->withBaseUri('https://jsonplaceholder.typicode.com')
        ->withToken('fake_bearer_token')
        ->post('/posts', [
            'title' => 'Post créé depuis PHP procédural',
            'body' => 'Contenu du post',
            'userId' => 1,
        ]);
    
    echo "Post créé avec ID: {$result['id']}\n\n";
} catch (Exception $e) {
    echo "Erreur: {$e->getMessage()}\n\n";
}

// ========================================
// EXEMPLE 3 : Avec proxy
// ========================================
echo "=== EXEMPLE 3 : Avec proxy ===\n";

$client3 = new GuzzleHttpClient();

try {
    $data = $client3
        ->withBaseUri('https://jsonplaceholder.typicode.com')
        ->withProxy('http://proxy.example.com:8080') // Remplacez par votre proxy
        ->setMaxRetries(5)
        ->setTimeouts(60, 20)
        ->get('/posts/1');
    
    echo "OK avec proxy\n\n";
} catch (Exception $e) {
    echo "Erreur: {$e->getMessage()}\n\n";
}

// ========================================
// EXEMPLE 4 : Auto-refresh token
// ========================================
echo "=== EXEMPLE 4 : Auto-refresh token ===\n";

$accessToken = 'old_token';
$refreshToken = 'refresh_token_123';

// Fonction pour refresh le token (simulée)
function refreshAccessToken($refreshToken) {
    // En production, appelez votre API OAuth ici
    echo "  → Refresh token appelé avec: $refreshToken\n";
    
    // Simulation d'un nouveau token
    return 'new_access_token_' . time();
}

$client4 = new GuzzleHttpClient();

try {
    $data = $client4
        ->withBaseUri('https://jsonplaceholder.typicode.com')
        ->withToken($accessToken)
        ->setTokenErrorIndicators(['Unauthorized', 'token_expired'])
        ->onTokenExpired(function() use ($refreshToken) {
            // Cette fonction est appelée automatiquement si token expiré
            return refreshAccessToken($refreshToken);
        })
        ->get('/posts/1');
    
    echo "Requête réussie\n\n";
} catch (Exception $e) {
    echo "Erreur: {$e->getMessage()}\n\n";
}

// ========================================
// EXEMPLE 5 : Logger personnalisé
// ========================================
echo "=== EXEMPLE 5 : Logger personnalisé ===\n";

$client5 = new GuzzleHttpClient();

// Logger vers un fichier
$client5->setLogger(function($level, $message, $context) {
    $log = "[" . strtoupper($level) . "] $message";
    if (!empty($context)) {
        $log .= " | " . json_encode($context);
    }
    echo "  LOG: $log\n";
});

try {
    $data = $client5
        ->withBaseUri('https://jsonplaceholder.typicode.com')
        ->get('/posts/1');
    
    echo "Requête réussie avec logs\n\n";
} catch (Exception $e) {
    echo "Erreur: {$e->getMessage()}\n\n";
}

// ========================================
// EXEMPLE 6 : Gestion d'erreurs
// ========================================
echo "=== EXEMPLE 6 : Gestion d'erreurs ===\n";

$client6 = new GuzzleHttpClient();

try {
    // URL invalide pour tester la gestion d'erreur
    $data = $client6
        ->withBaseUri('https://jsonplaceholder.typicode.com')
        ->get('/invalid-endpoint-404');
    
} catch (RuntimeException $e) {
    echo "Exception capturée: {$e->getMessage()}\n";
    
    // Récupérer la réponse brute
    $lastResponse = $client6->getLastResponse();
    if ($lastResponse) {
        echo "Status code: " . $lastResponse->getStatusCode() . "\n";
    }
}

echo "\n=== Tous les exemples terminés ===\n";
