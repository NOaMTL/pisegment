<?php

namespace App\Console\Commands;

use App\Services\ExternalApi\CronApiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sync:external-api')]
#[Description('Synchronise les données avec l\'API externe (appelé par CRON)')]
class SyncExternalApi extends Command
{
    public function __construct(
        private CronApiService $apiService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Démarrage de la synchronisation avec l\'API externe...');
        $this->newLine();

        try {
            // Afficher le statut du token
            $tokenInfo = $this->apiService->getTokenInfo();
            if ($tokenInfo['has_token']) {
                $this->info('✅ Token trouvé en cache');
            } else {
                $this->info('🔐 Authentification nécessaire...');
            }

            // Exemple 1: Récupérer des données
            $this->info('📥 Récupération des données...');
            $data = $this->apiService->fetchData([
                'date' => now()->format('Y-m-d'),
                'limit' => 100,
            ]);

            $this->info('✅ Données récupérées: '.count($data).' éléments');

            // Exemple 2: Envoyer des données
            $this->info('📤 Envoi de données...');
            $result = $this->apiService->sendData([
                'type' => 'sync',
                'timestamp' => now()->toIso8601String(),
                'items' => [
                    ['id' => 1, 'status' => 'processed'],
                    ['id' => 2, 'status' => 'pending'],
                ],
            ]);

            $this->info('✅ Données envoyées avec succès');

            // Exemple 3: Récupérer un rapport (si applicable)
            // $report = $this->apiService->getReport('daily-report-'.now()->format('Ymd'));
            // $this->info('✅ Rapport récupéré');

            $this->newLine();
            $this->info('✨ Synchronisation terminée avec succès !');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la synchronisation:');
            $this->error($e->getMessage());
            $this->newLine();
            $this->warn('💡 Vérifiez:');
            $this->warn('  - La configuration dans config/services.php');
            $this->warn('  - Les credentials dans .env');
            $this->warn('  - La disponibilité du proxy');
            $this->warn('  - Les logs: storage/logs/laravel.log');

            return Command::FAILURE;
        }
    }
}
