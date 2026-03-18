<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasLifeInsurance = fake()->boolean(30);
        $hasHomeLoan = fake()->boolean(20);
        $hasCarLoan = fake()->boolean(15);
        $insuranceCount = ($hasLifeInsurance ? 1 : 0) + ($hasHomeLoan ? 1 : 0) + ($hasCarLoan ? 1 : 0);

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'birth_date' => fake()->dateTimeBetween('-70 years', '-18 years'),
            'city' => fake()->randomElement(['Bordeaux', 'Mérignac', 'Pessac', 'Talence', 'Bègles', 'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice']),
            'postal_code' => fake()->postcode(),
            'average_balance' => fake()->randomFloat(2, 100, 50000),
            'monthly_income' => fake()->randomFloat(2, 1200, 15000),
            'has_life_insurance' => $hasLifeInsurance,
            'has_home_loan' => $hasHomeLoan,
            'has_car_loan' => $hasCarLoan,
            'insurance_count' => $insuranceCount,
            'payment_incidents' => fake()->numberBetween(0, 5),
            'last_contact_at' => fake()->boolean(60) ? fake()->dateTimeBetween('-1 year', 'now') : null,
        ];
    }
}
