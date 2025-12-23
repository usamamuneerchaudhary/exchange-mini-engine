<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Usama Munir',
                'email' => 'usama@test.com',
                'password' => Hash::make('password'),
                'balance' => 100000.00, // $100k USD
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Asad Butt',
                'email' => 'butt@test.com',
                'password' => Hash::make('password'),
                'balance' => 50000.00, // $50k USD
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Ali Hamza',
                'email' => 'ali@test.com',
                'password' => Hash::make('password'),
                'balance' => 25000.00, // $25k USD
                'email_verified_at' => now(),
            ]
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
