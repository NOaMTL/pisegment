# CRON API Externe - Démarrage Rapide ⚡

## Votre Use Case

Vous avez un **CRON** qui doit :
- ✅ Appeler une API externe régulièrement
- ✅ S'authentifier (client_credentials) - **pas de refresh token**
- ✅ Gérer un proxy lent (retry multiple)
- ✅ Auto-refresh si token expiré

## 🚀 En 3 Étapes

### 1️⃣ Configuration (2 minutes)

**Ajoutez dans `.env`** :
```env
EXTERNAL_API_BASE_URI=https://api.external.com
EXTERNAL_API_CLIENT_ID=your_client_id
EXTERNAL_API_CLIENT_SECRET=your_client_secret
EXTERNAL_API_PROXY=http://proxy.company.com:8080  # Optionnel
```

### 2️⃣ Test (30 secondes)

```bash
php artisan sync:external-api
```

**✅ Si succès**, vous verrez :
```
🚀 Démarrage de la synchronisation...
🔐 Authentification nécessaire...
✅ Données récupérées: 42 éléments
✨ Synchronisation terminée !
```

### 3️⃣ Activer le CRON (1 minute)

**Ajoutez dans `routes/console.php`** :
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('sync:external-api')->everyThirtyMinutes();
```

**Configurez le crontab** :
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

**C'est tout ! ✨**

---

## 📖 Documentation Complète

- **Guide complet** : [CRON_GUIDE.md](CRON_GUIDE.md)
- **Service** : [CronApiService.php](CronApiService.php)
- **Commande** : `app/Console/Commands/SyncExternalApi.php`
- **HTTP Client** : `app/Services/Http/README.md`

---

## 🔧 Personnalisation Rapide

### Modifier ce qui est appelé

Éditez **`CronApiService.php`** :

```php
public function maMethode(): array
{
    return $this->httpClient
        ->withToken($this->getAccessToken())
        ->get('/mon/endpoint');
}
```

### Modifier la logique du CRON

Éditez **`SyncExternalApi.php`** ligne ~35 :

```php
public function handle(): int
{
    $data = $this->apiService->fetchData();
    
    // Votre logique ici
    foreach ($data as $item) {
        // Traiter les données
    }
    
    return Command::SUCCESS;
}
```

---

## 🛠️ Troubleshooting

### Proxy timeout ?

Dans `CronApiService.php` ligne 45, augmentez :
```php
->setMaxRetries(10)      // Plus de tentatives
->setTimeouts(180, 60)   // Timeouts plus longs
```

### Token périme trop vite ?

Ligne 19, réduisez :
```php
private const TOKEN_LIFETIME_MINUTES = 30; // Au lieu de 55
```

### Voir les logs ?

```bash
tail -f storage/logs/laravel.log
```

---

## ✅ Comment ça Marche ?

```
CRON démarre
    ↓
CronApiService::fetchData()
    ↓
Vérifie cache → Token en cache ?
    ↓ NON
Authentifie (client_credentials) → Obtient token
    ↓
Stocke en cache (55 min)
    ↓
Fait la requête API avec token
    ↓
Si erreur "token_expired" → Redemande un token
    ↓
Retry automatique (5 tentatives, backoff exponentiel)
    ↓
Retourne les données
```

---

## 🎯 Fichiers Créés Pour Vous

```
app/
├── Console/Commands/
│   └── SyncExternalApi.php          ← Commande CRON
├── Services/
    ├── ExternalApi/
    │   ├── CronApiService.php        ← Service principal
    │   ├── CRON_GUIDE.md             ← Guide complet
    │   └── QUICKSTART_CRON.md        ← Ce fichier
    └── Http/
        ├── GuzzleHttpClient.php      ← Client HTTP (indépendant Laravel)
        └── HttpClientInterface.php

config/
└── services.php                      ← Configuration ajoutée

.env.cron-api.example                 ← Template variables
```

---

## 🚀 Exemple Complet d'Utilisation

```php
use App\Services\ExternalApi\CronApiService;

class MaCronCommand extends Command
{
    public function handle(CronApiService $api): int
    {
        try {
            // Récupérer des données (auth auto si besoin)
            $data = $api->fetchData(['date' => today()]);
            
            // Traiter
            foreach ($data as $item) {
                $this->info("Traitement: {$item['id']}");
                // Votre logique métier
            }
            
            // Envoyer un résultat
            $api->sendData([
                'processed' => count($data),
                'status' => 'success'
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

---

**🎉 Votre CRON est prêt !** Consultez [CRON_GUIDE.md](CRON_GUIDE.md) pour plus de détails.
