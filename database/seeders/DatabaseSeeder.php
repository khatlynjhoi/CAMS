<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultUsers = [
            [
                'name' => 'IT Administrator',
                'email' => 'it@bma.edu.ph',
                'password' => Hash::make('Admin123!'),
                'role' => 'Admin',
                'department' => 'ICT',
                'status' => 'Active',
                'password_changed_at' => now(),
            ],
        ];

        foreach ($defaultUsers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}