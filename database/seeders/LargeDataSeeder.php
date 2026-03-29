<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class LargeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Génère 50 000 customers pour simuler une grosse base de données
     */
    public function run(): void
    {
        $this->command->info('🚀 Génération de 50 000 customers...');

        // Désactiver les events pour accélérer l'insertion
        Customer::unguard();

        // Créer par batch de 1000 pour optimiser la mémoire
        $batchSize = 1000;
        $totalRecords = 50000;
        $batches = $totalRecords / $batchSize;

        for ($i = 0; $i < $batches; $i++) {
            Customer::factory()
                ->count($batchSize)
                ->create();

            $progress = ($i + 1) * $batchSize;
            $this->command->info("✓ {$progress} / {$totalRecords} clients créés...");
        }

        Customer::reguard();

        $this->command->info("✅ {$totalRecords} customers créés avec succès !");
    }
}
