<?php

namespace App\Services\SegmentBuilder;

class AvailableFields
{
    /**
     * Get all available fields for segment building.
     *
     * @return array<string, array>
     */
    public static function all(): array
    {
        return [
            'identity' => [
                'label' => 'Identité',
                'fields' => [
                    'age' => [
                        'label' => 'Âge',
                        'field' => 'birth_date',
                        'type' => FieldType::Number,
                        'description' => 'Âge du client',
                        'operators' => array_map(
                            fn ($op) => ['value' => $op->value, 'label' => $op->label()],
                            FieldType::Number->getAvailableOperators()
                        ),
                    ],
                    'city' => [
                        'label' => 'Ville',
                        'field' => 'city',
                        'type' => FieldType::Select,
                        'description' => 'Ville de résidence',
                        'options' => ['Bordeaux', 'Mérignac', 'Pessac', 'Talence', 'Bègles', 'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice'],
                        'operators' => array_map(
                            fn ($op) => ['value' => $op->value, 'label' => $op->label()],
                            FieldType::Select->getAvailableOperators()
                        ),
                    ],
                ],
            ],
            'banking' => [
                'label' => 'Situation bancaire',
                'fields' => [
                    'average_balance' => [
                        'label' => 'Solde moyen',
                        'field' => 'average_balance',
                        'type' => FieldType::Number,
                        'description' => 'Solde moyen du compte',
                        'operators' => array_map(
                            fn ($op) => ['value' => $op->value, 'label' => $op->label()],
                            FieldType::Number->getAvailableOperators()
                        ),
                    ],
                    'monthly_income' => [
                        'label' => 'Revenus mensuels',
                        'field' => 'monthly_income',
                        'type' => FieldType::Number,
                        'description' => 'Revenus mensuels déclarés',
                        'operators' => array_map(
                            fn ($op) => ['value' => $op->value, 'label' => $op->label()],
                            FieldType::Number->getAvailableOperators()
                        ),
                    ],
                ],
            ],
            'products' => [
                'label' => 'Produits détenus',
                'fields' => [
                    'has_life_insurance' => [
                        'label' => 'Assurance-vie',
                        'field' => 'has_life_insurance',
                        'type' => FieldType::Boolean,
                        'description' => 'Possède une assurance-vie',
                        'operators' => array_map(
                            fn ($op) => ['value' => $op->value, 'label' => $op->label()],
                            FieldType::Boolean->getAvailableOperators()
                        ),
                    ],
                    'has_home_loan' => [
                        'label' => 'Crédit immobilier',
                        'field' => 'has_home_loan',
                        'type' => FieldType::Boolean,
                        'description' => 'Possède un crédit immobilier',
                        'operators' => array_map(
                            fn ($op) => ['value' => $op->value, 'label' => $op->label()],
                            FieldType::Boolean->getAvailableOperators()
                        ),
                    ],
                    'has_car_loan' => [
                        'label' => 'Crédit automobile',
                        'field' => 'has_car_loan',
                        'type' => FieldType::Boolean,
                        'description' => 'Possède un crédit automobile',
                        'operators' => array_map(
                            fn ($op) => ['value' => $op->value, 'label' => $op->label()],
                            FieldType::Boolean->getAvailableOperators()
                        ),
                    ],
                    'insurance_count' => [
                        'label' => 'Nombre d\'assurances',
                        'field' => 'insurance_count',
                        'type' => FieldType::Number,
                        'description' => 'Nombre total d\'assurances souscrites',
                        'operators' => array_map(
                            fn ($op) => ['value' => $op->value, 'label' => $op->label()],
                            FieldType::Number->getAvailableOperators()
                        ),
                    ],
                ],
            ],
            'activity' => [
                'label' => 'Activité du compte',
                'fields' => [
                    'payment_incidents' => [
                        'label' => 'Incidents de paiement',
                        'field' => 'payment_incidents',
                        'type' => FieldType::Number,
                        'description' => 'Nombre d\'incidents de paiement',
                    ],
                    'last_contact_at' => [
                        'label' => 'Dernier contact',
                        'field' => 'last_contact_at',
                        'type' => FieldType::Date,
                        'description' => 'Date du dernier contact',
                    ],
                ],
            ],
        ];
    }

    /**    'operators' => array_map(
                            fn($op) => ['value' => $op->value, 'label' => $op->label()],
                            FieldType::Number->getAvailableOperators()
                        ),

     * Get a specific field configuration.
     */
    public static function get(string $fieldKey): ?array
    {
        foreach (self::all() as $category) {
            if (isset($category['fields'][$fieldKey])) {
                return $category['fields'][$fieldKey];
            }
        }

        return null;
    }
}
