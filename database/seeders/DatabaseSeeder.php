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
            ['email' => 'admin@majadigi.com'],
            [
                'name' => 'Admin Majadigi',
                'username' => 'admin_majadigi',
                'role' => 'admin',
                'password' => bcrypt('password'), // Set password manually
                'email_verified_at' => now(),
            ]
        );

        // Akun Tester
        User::updateOrCreate(
            ['email' => 'tester@majadigi.com'],
            [
                'name' => 'Tester Majadigi',
                'username' => 'tester_majadigi',
                'role' => 'tester',
                'password' => bcrypt('password'), // Set password manually
                'email_verified_at' => now(),
            ]
        );
    }
}
