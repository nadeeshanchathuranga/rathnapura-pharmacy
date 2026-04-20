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
        // Admin User - Full Access
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'Admin', 'password' => 'TRSRIYAS8696', 'role' => 0]
        );

        // Backoffice - Can manage products and GRNs
        User::firstOrCreate(
            ['email' => 'backoffice@gmail.com'],
            ['name' => 'Backoffice', 'password' => 'RR202020', 'role' => 1]
        );

        // Cashier - access to all divisions
        User::firstOrCreate(
            ['email' => 'cashier1@gmail.com'],
            ['name' => 'Cashier', 'password' => 'RR576700', 'role' => 2, 'division_id' => null]
        );

        // Cashier - access to all divisions
        User::firstOrCreate(
            ['email' => 'cashier2@gmail.com'],
            ['name' => 'Cashier', 'password' => 'CAPLUS12000', 'role' => 2, 'division_id' => null]
        );

        // Token Counter Cashiers
        User::firstOrCreate(
            ['email' => 'tc1@gmail.com'],
            ['name' => 'Token Cashier', 'password' => 'TRS1900', 'role' => 3, 'division_id' => null]
        );

        User::firstOrCreate(
            ['email' => 'tc2@gmail.com'],
            ['name' => 'Token Cashier', 'password' => 'TRS3070', 'role' => 3, 'division_id' => null]
        );

        User::firstOrCreate(
            ['email' => 'tc3@gmail.com'],
            ['name' => 'Token Cashier', 'password' => 'Thikshani7575', 'role' => 3, 'division_id' => null]
        );

        User::firstOrCreate(
            ['email' => 'tc4@gmail.com'],
            ['name' => 'Token Cashier', 'password' => 'RIYAS156', 'role' => 3, 'division_id' => null]
        );

    }
}
