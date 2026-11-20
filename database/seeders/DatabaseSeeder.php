<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Akun Admin
        User::updateOrCreate(
            ['email' => 'admin@simonter.id'],
            [
                'name' => 'Admin SIMONTER',
                'username' => 'admin_simonter',
                'role' => 'admin',
                'password' => bcrypt('password'), // Set password manually
            ]
        );

        // Akun Tester
        User::updateOrCreate(
            ['email' => 'tester@simonter.id'],
            [
                'name' => 'Tester SIMONTER',
                'username' => 'tester_simonter',
                'role' => 'tester',
                'password' => bcrypt('password'), // Set password manually
            ]
        );
    }
}
