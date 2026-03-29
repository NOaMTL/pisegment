<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FilterGroup;
use Illuminate\Database\Seeder;

class FilterGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            [
                'name' => 'Identité',
                'label' => 'Identité',
                'description' => 'Informations d\'identification du client',
                'icon' => 'User',
                'order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Situation bancaire',
                'label' => 'Situation bancaire',
                'description' => 'Données financières et bancaires',
                'icon' => 'Wallet',
                'order' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Produits détenus',
                'label' => 'Produits détenus',
                'description' => 'Produits et services souscrits',
                'icon' => 'ShoppingBag',
                'order' => 30,
                'is_active' => true,
            ],
        ];

        foreach ($groups as $group) {
            FilterGroup::updateOrCreate(
                ['name' => $group['name']],
                $group
            );
        }
    }
}
