<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
            ['name' => 'Admin', 'password' => Hash::make('TRSRIYAS8696'), 'role' => 0]
        );

        // Backoffice - Can manage products and GRNs
        User::firstOrCreate(
            ['email' => 'backoffice@gmail.com'],
            ['name' => 'Backoffice', 'password' => Hash::make('RR202020'), 'role' => 1]
        );

        // Cashier - access to all divisions
        User::firstOrCreate(
            ['email' => 'cashier1@gmail.com'],
            ['name' => 'Cashier', 'password' => Hash::make('RR576700'), 'role' => 2, 'division_id' => null]
        );

        // Cashier - access to all divisions
        User::firstOrCreate(
            ['email' => 'cashier2@gmail.com'],
            ['name' => 'Cashier', 'password' => Hash::make('CAPLUS12000'), 'role' => 2, 'division_id' => null]
        );

        // Token Counter Cashiers
        User::firstOrCreate(
            ['email' => 'tc1@gmail.com'],
            ['name' => 'Token Cashier', 'password' => Hash::make('TRS1900'), 'role' => 3, 'division_id' => null]
        );

        User::firstOrCreate(
            ['email' => 'tc2@gmail.com'],
            ['name' => 'Token Cashier', 'password' => Hash::make('TRS3070'), 'role' => 3, 'division_id' => null]
        );

        User::firstOrCreate(
            ['email' => 'tc3@gmail.com'],
            ['name' => 'Token Cashier', 'password' => Hash::make('Thikshani7575'), 'role' => 3, 'division_id' => null]
        );

        User::firstOrCreate(
            ['email' => 'tc4@gmail.com'],
            ['name' => 'Token Cashier', 'password' => Hash::make('RIYAS156'), 'role' => 3, 'division_id' => null]
        );

    }
}
