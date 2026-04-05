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
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'Admin', 'password' => bcrypt($pass), 'role' => 0]
        );

        // Backoffice - Can manage products and GRNs
        User::firstOrCreate(
            ['email' => 'backoffice@gmail.com'],
            ['name' => 'Backoffice', 'password' => bcrypt($pass), 'role' => 1]
        );

        // Pharmacy Cashier
        User::firstOrCreate(
            ['email' => 'pharmacy@gmail.com'],
            ['name' => 'Pharmacy Cashier', 'password' => bcrypt($pass), 'role' => 2, 'division_id' => 1]
        );

        // Sports Cashier
        User::firstOrCreate(
            ['email' => 'sports@gmail.com'],
            ['name' => 'Sports Cashier', 'password' => bcrypt($pass), 'role' => 2, 'division_id' => 2]
        );

        // Both Divisions Cashier - access to all divisions
        User::firstOrCreate(
            ['email' => 'cashier@gmail.com'],
            ['name' => 'Cashier', 'password' => bcrypt($pass), 'role' => 2, 'division_id' => null]
        );
        
        User::firstOrCreate(
            ['email' => 'tokencashier@gmail.com'],
            ['name' => 'Token Cashier', 'password' => bcrypt($pass), 'role' => 3, 'division_id' => null]
        );

    }
}
