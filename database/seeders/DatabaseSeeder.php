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
            ['email' => 'admin@layananterpadu.id'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'role' => 'admin',
                'password' => bcrypt('password'),
            ]
        );
        User::updateOrCreate(
            ['email' => 'admin@simonter.id'],
            [
                'name' => 'Administrator',
                'username' => 'admin_simonter',
                'role' => 'admin',
                'password' => bcrypt('password'),
            ]
        );

        // Akun Tester
        User::updateOrCreate(
            ['email' => 'tester@layananterpadu.id'],
            [
                'name' => 'Tester Layanan Terpadu',
                'username' => 'tester',
                'role' => 'tester',
                'password' => bcrypt('password'),
            ]
        );
        User::updateOrCreate(
            ['email' => 'tester@simonter.id'],
            [
                'name' => 'Tester Layanan Terpadu',
                'username' => 'tester_simonter',
                'role' => 'tester',
                'password' => bcrypt('password'),
            ]
        );
    }
}
