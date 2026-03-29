<?php

namespace Database\Seeders;

use App\Models\FilterField;
use Illuminate\Database\Seeder;

class FilterFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            // Identité
            [
                'name' => 'age',
                'label' => 'Âge',
                'field_type' => 'number',
                'sql_column' => 'age',
                'group' => 'Identité',
                'description' => 'Âge du client',
                'operators' => [
                    ['value' => '=', 'label' => 'est égal à'],
                    ['value' => '!=', 'label' => 'est différent de'],
                    ['value' => '>', 'label' => 'est supérieur à'],
                    ['value' => '>=', 'label' => 'est supérieur ou égal à'],
                    ['value' => '<', 'label' => 'est inférieur à'],
                    ['value' => '<=', 'label' => 'est inférieur ou égal à'],
                    ['value' => 'between', 'label' => 'est entre'],
                ],
                'order' => 10,
            ],
            [
                'name' => 'city',
                'label' => 'Ville',
                'field_type' => 'select',
                'sql_column' => 'city',
                'group' => 'Identité',
                'description' => 'Ville de résidence',
                'options' => ['Bordeaux', 'Mérignac', 'Pessac', 'Talence', 'Bègles', 'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice'],
                'operators' => [
                    ['value' => '=', 'label' => 'est égal à'],
                    ['value' => '!=', 'label' => 'est différent de'],
                ],
                'order' => 20,
            ],

            // Situation bancaire
            [
                'name' => 'average_balance',
                'label' => 'Solde moyen',
                'field_type' => 'number',
                'sql_column' => 'average_balance',
                'group' => 'Situation bancaire',
                'description' => 'Solde moyen du compte',
                'operators' => [
                    ['value' => '=', 'label' => 'est égal à'],
                    ['value' => '!=', 'label' => 'est différent de'],
                    ['value' => '>', 'label' => 'est supérieur à'],
                    ['value' => '>=', 'label' => 'est supérieur ou égal à'],
                    ['value' => '<', 'label' => 'est inférieur à'],
                    ['value' => '<=', 'label' => 'est inférieur ou égal à'],
                    ['value' => 'between', 'label' => 'est entre'],
                ],
                'order' => 30,
            ],
            [
                'name' => 'monthly_income',
                'label' => 'Revenus mensuels',
                'field_type' => 'number',
                'sql_column' => 'monthly_income',
                'group' => 'Situation bancaire',
                'description' => 'Revenus mensuels déclarés',
                'operators' => [
                    ['value' => '=', 'label' => 'est égal à'],
                    ['value' => '!=', 'label' => 'est différent de'],
                    ['value' => '>', 'label' => 'est supérieur à'],
                    ['value' => '>=', 'label' => 'est supérieur ou égal à'],
                    ['value' => '<', 'label' => 'est inférieur à'],
                    ['value' => '<=', 'label' => 'est inférieur ou égal à'],
                    ['value' => 'between', 'label' => 'est entre'],
                ],
                'order' => 40,
            ],

            // Produits détenus
            [
                'name' => 'has_life_insurance',
                'label' => 'Assurance-vie',
                'field_type' => 'boolean',
                'sql_column' => 'has_life_insurance',
                'group' => 'Produits détenus',
                'description' => 'Possède une assurance-vie',
                'operators' => [
                    ['value' => '=', 'label' => 'est'],
                ],
                'order' => 50,
            ],
            [
                'name' => 'has_home_loan',
                'label' => 'Crédit immobilier',
                'field_type' => 'boolean',
                'sql_column' => 'has_home_loan',
                'group' => 'Produits détenus',
                'description' => 'Possède un crédit immobilier',
                'operators' => [
                    ['value' => '=', 'label' => 'est'],
                ],
                'order' => 60,
            ],
            [
                'name' => 'has_car_loan',
                'label' => 'Crédit automobile',
                'field_type' => 'boolean',
                'sql_column' => 'has_car_loan',
                'group' => 'Produits détenus',
                'description' => 'Possède un crédit automobile',
                'operators' => [
                    ['value' => '=', 'label' => 'est'],
                ],
                'order' => 70,
            ],
            [
                'name' => 'insurance_count',
                'label' => "Nombre d'assurances",
                'field_type' => 'number',
                'sql_column' => 'insurance_count',
                'group' => 'Produits détenus',
                'description' => "Nombre total d'assurances souscrites",
                'operators' => [
                    ['value' => '=', 'label' => 'est égal à'],
                    ['value' => '!=', 'label' => 'est différent de'],
                    ['value' => '>', 'label' => 'est supérieur à'],
                    ['value' => '>=', 'label' => 'est supérieur ou égal à'],
                    ['value' => '<', 'label' => 'est inférieur à'],
                    ['value' => '<=', 'label' => 'est inférieur ou égal à'],
                    ['value' => 'between', 'label' => 'est entre'],
                ],
                'order' => 80,
            ],
        ];

        foreach ($fields as $field) {
            FilterField::updateOrCreate(
                ['name' => $field['name']],
                $field
            );
        }
    }
}
