<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Set default seeded user password and hash with bcrypt
        $pass = '123456789';

        // Admin User - Full Access
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt($pass),
            'role' => 0,
        ]);

        // Manager - All access except settings and setting reports
        User::create([
            'name' => 'Manager',
            'email' => 'manager@gmail.com',
            'password' => bcrypt($pass),
            'role' => 1,
        ]);

        // Cashier - Only sales and sales reports
        User::create([
            'name' => 'Cashier',
            'email' => 'cashier@gmail.com',
            'password' => bcrypt($pass),
            'role' => 2,
        ]);

        // Store Keeper - Inventory management
        User::create([
            'name' => 'Store-Keeper',
            'email' => 'storekeeper@gmail.com',
            'password' => bcrypt($pass),
            'role' => 3,
        ]);


    }
}
