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
        User::create([
            'name' => 'Admin Majadigi',
            'email' => 'admin@majadigi.com',
            'username' => 'admin_majadigi',
            'role' => 'admin',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        // Akun Tester
        User::create([
            'name' => 'Tester Majadigi',
            'email' => 'tester@majadigi.com',
            'username' => 'tester_majadigi',
            'role' => 'tester',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
    }
}
