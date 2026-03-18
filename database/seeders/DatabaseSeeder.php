<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users with different roles
        User::factory()->create([
            'name' => 'Agent Test',
            'email' => 'agent@example.com',
            'password' => 'password',
            'role' => 'agent',
        ]);

        User::factory()->create([
            'name' => 'Manager Test',
            'email' => 'manager@example.com',
            'password' => 'password',
            'role' => 'agency_manager',
        ]);

        User::factory()->create([
            'name' => 'Staff Test',
            'email' => 'staff@example.com',
            'password' => 'password',
            'role' => 'staff',
        ]);

        // Create additional agents
        User::factory(5)->create(['role' => 'agent']);

        // Seed customers
        $this->call([
            CustomerSeeder::class,
        ]);
    }
}
