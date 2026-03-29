# Guide CRON - API Externe

Guide complet pour configurer votre CRON qui synchronise avec une API externe.

## 📋 Votre Use Case

✅ **Cron régulier** qui appelle une API externe  
✅ **Authentification complète** (client_credentials) - pas de refresh token  
✅ **Support proxy lent** avec retry multiple (5 tentatives, timeouts 90s)  
✅ **Token en cache** pour éviter auth répétées (valide 55 minutes)  
✅ **Auto-refresh** si token expiré au milieu d'une requête  

---

## 🚀 Installation

### 1. Configuration .env

Ajoutez ces variables à votre `.env` (voir `.env.cron-api.example`) :

```env
EXTERNAL_API_BASE_URI=https://api.external.com
EXTERNAL_API_CLIENT_ID=your_client_id
EXTERNAL_API_CLIENT_SECRET=your_client_secret
EXTERNAL_API_PROXY=http://proxy.company.com:8080  # Optionnel
```

### 2. Vérification de la configuration

```bash
php artisan config:show services.external_api
```

Vous devriez voir :
```
base_uri .................................. https://api.external.com
client_id ................................. your_client_id
client_secret ............................. your_client_secret
proxy ..................................... http://proxy.company.com:8080
```

---

## 🧪 Test Manuel

### Tester la commande

```bash
php artisan sync:external-api
```

**Sortie attendue** :
```
🚀 Démarrage de la synchronisation avec l'API externe...

🔐 Authentification nécessaire...
📥 Récupération des données...
✅ Données récupérées: 42 éléments
📤 Envoi de données...
✅ Données envoyées avec succès

✨ Synchronisation terminée avec succès !
```

### Tester avec verbose (pour voir les logs)

```bash
php artisan sync:external-api -v
```

---

## ⏰ Configuration CRON

### Ajouter dans `app/Console/Kernel.php` ou `routes/console.php`

**Laravel 11+ (routes/console.php)** :

```php
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\SyncExternalApi;

// Toutes les 30 minutes
Schedule::command(SyncExternalApi::class)->everyThirtyMinutes();

// Ou toutes les heures
Schedule::command(SyncExternalApi::class)->hourly();

// Ou chaque jour à 2h du matin
Schedule::command(SyncExternalApi::class)->dailyAt('02:00');

// Ou toutes les 15 minutes
Schedule::command(SyncExternalApi::class)->everyFifteenMinutes();
```

**Laravel 10 (app/Console/Kernel.php)** :

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('sync:external-api')
        ->everyThirtyMinutes()
        ->withoutOverlapping() // Évite les exécutions simultanées
        ->runInBackground();   // Exécution en arrière-plan
}
```

### Configuration du CRON système

Ajoutez cette ligne à votre crontab (`crontab -e`) :

```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔧 Personnalisation du Service

### Modifier les endpoints appelés

Éditez `app/Services/ExternalApi/CronApiService.php` :

```php
public function yourCustomMethod(): array
{
    return $this->httpClient
        ->withToken($this->getAccessToken())
        ->get('/your/endpoint', [
            'query' => ['param' => 'value']
        ]);
}
```

### Modifier la commande

Éditez `app/Console/Commands/SyncExternalApi.php` :

```php
public function handle(): int
{
    // Votre logique métier ici
    $data = $this->apiService->fetchData();
    
    // Traiter les données
    foreach ($data as $item) {
        // ...
    }
    
    return Command::SUCCESS;
}
```

---

## 🔍 Debugging

### Vérifier le statut du token

```bash
php artisan tinker --execute "app(\App\Services\ExternalApi\CronApiService::class)->getTokenInfo()"
```

Retourne :
```php
[
    "has_token" => true,
    "token_preview" => "eyJhbGciOiJIUzI1NiIs...",
    "cache_key" => "cron_api_access_token",
    "lifetime_minutes" => 55,
]
```

### Forcer une nouvelle authentification

```bash
php artisan tinker --execute "app(\App\Services\ExternalApi\CronApiService::class)->forceReauthenticate()"
```

### Vider le cache du token

```bash
php artisan cache:forget cron_api_access_token
```

### Consulter les logs

```bash
tail -f storage/logs/laravel.log
```

Vous verrez :
```
[info] Pas de token en cache, authentification...
[info] Authentification API externe en cours...
[info] Authentification réussie, token stocké en cache
[debug] Token trouvé en cache
```

### Activer le logging HTTP

Dans `CronApiService::configureClient()`, ajoutez :

```php
// Dans Laravel
$this->httpClient->setLogger(function($level, $message, $context) {
    \Log::$level($message, $context);
});
```

---

## 🛠️ Gestion des Erreurs

### Erreur: "Authentication failed"

**Causes possibles** :
- ❌ Credentials invalides dans `.env`
- ❌ Endpoint d'auth incorrect
- ❌ Proxy bloque la requête

**Solutions** :
```bash
# Vérifier la config
php artisan config:clear
php artisan config:show services.external_api

# Tester l'authentification manuellement
php artisan tinker
>>> app(\App\Services\ExternalApi\CronApiService::class)->forceReauthenticate()
```

### Erreur: "Connection timeout"

**Causes possibles** :
- ❌ Proxy lent ou injoignable
- ❌ API externe down
- ❌ Firewall bloque la sortie

**Solutions** :
```php
// Dans CronApiService::configureClient(), augmentez les timeouts
->setMaxRetries(10)      // 10 tentatives au lieu de 5
->setTimeouts(180, 60)   // 180s timeout, 60s connexion
```

### Erreur: "Token expired" en boucle

**Cause** : L'API retourne un token déjà expiré

**Solution** :
```php
// Dans CronApiService.php, réduisez TOKEN_LIFETIME_MINUTES
private const TOKEN_LIFETIME_MINUTES = 30; // Au lieu de 55
```

### Erreur: "Max retries reached"

**Cause** : Le proxy refuse la connexion après 5 tentatives

**Solution** :
```bash
# Vérifier que le proxy est accessible
curl -x http://proxy.company.com:8080 https://api.external.com

# Ou testez sans proxy temporairement
EXTERNAL_API_PROXY= php artisan sync:external-api
```

---

## 📊 Monitoring

### Créer une notification en cas d'échec

Dans `SyncExternalApi::handle()` :

```php
use Illuminate\Support\Facades\Mail;

public function handle(): int
{
    try {
        $this->apiService->fetchData();
        return Command::SUCCESS;
    } catch (\Exception $e) {
        // Envoyer un email d'alerte
        Mail::to('admin@example.com')->send(
            new CronFailedMail($e->getMessage())
        );
        
        return Command::FAILURE;
    }
}
```

### Logger dans une table dédiée

```php
use App\Models\CronLog;

public function handle(): int
{
    $log = CronLog::create([
        'command' => 'sync:external-api',
        'started_at' => now(),
    ]);
    
    try {
        $data = $this->apiService->fetchData();
        
        $log->update([
            'status' => 'success',
            'completed_at' => now(),
            'records_processed' => count($data),
        ]);
        
        return Command::SUCCESS;
    } catch (\Exception $e) {
        $log->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $e->getMessage(),
        ]);
        
        return Command::FAILURE;
    }
}
```

---

## 🎯 Bonnes Pratiques

### ✅ Utiliser withoutOverlapping

Évite qu'une nouvelle exécution démarre si la précédente n'est pas terminée :

```php
Schedule::command('sync:external-api')
    ->everyThirtyMinutes()
    ->withoutOverlapping();
```

### ✅ Configurer un timeout maximum

```php
Schedule::command('sync:external-api')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->timeout(300); // 5 minutes max
```

### ✅ Notification en cas d'échec

```php
Schedule::command('sync:external-api')
    ->everyThirtyMinutes()
    ->emailOutputOnFailure('admin@example.com');
```

### ✅ Logs dans un fichier séparé

Dans `config/logging.php` :

```php
'channels' => [
    'cron' => [
        'driver' => 'daily',
        'path' => storage_path('logs/cron.log'),
        'level' => 'info',
        'days' => 14,
    ],
],
```

Dans `CronApiService.php` :

```php
use Illuminate\Support\Facades\Log;

Log::channel('cron')->info('Synchronisation démarrée');
```

---

## 🔐 Sécurité

### ✅ Ne pas logger les secrets

```php
// ❌ MAUVAIS
Log::info('Auth response', $response); // Peut contenir le token

// ✅ BON
Log::info('Auth successful', [
    'token_preview' => substr($response['access_token'], 0, 10).'...'
]);
```

### ✅ Chiffrer les credentials

Utilisez Laravel Encrypter si vous stockez des credentials en DB :

```php
use Illuminate\Support\Facades\Crypt;

$encrypted = Crypt::encryptString($clientSecret);
$decrypted = Crypt::decryptString($encrypted);
```

### ✅ Rotate les credentials régulièrement

Mettez en place une rotation des `CLIENT_ID` et `CLIENT_SECRET` tous les 90 jours.

---

## 📝 Checklist Déploiement

- [ ] Variables `.env` configurées
- [ ] Config cache cleared : `php artisan config:clear`
- [ ] Test manuel réussi : `php artisan sync:external-api`
- [ ] CRON ajouté dans `routes/console.php`
- [ ] CRON système configuré : `* * * * * php artisan schedule:run`
- [ ] Logs vérifiés : `storage/logs/laravel.log`
- [ ] Monitoring configuré (optionnel)
- [ ] Notifications d'échec configurées (optionnel)

---

## 🆘 Support

**Problème persistant ?**

1. Vérifiez les logs : `storage/logs/laravel.log`
2. Testez manuellement : `php artisan sync:external-api -v`
3. Vérifiez la connectivité proxy : `curl -x $PROXY $API_URL`
4. Forcez un nouveau token : `php artisan tinker` → `forceReauthenticate()`
5. Consultez la doc HTTP Client : `app/Services/Http/README.md`

**Fichiers clés** :
- Service : `app/Services/ExternalApi/CronApiService.php`
- Commande : `app/Console/Commands/SyncExternalApi.php`
- Configuration : `config/services.php` + `.env`
- HTTP Client : `app/Services/Http/GuzzleHttpClient.php`
