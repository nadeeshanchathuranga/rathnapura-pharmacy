<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        Currency::insert([
            [
                'name'   => 'Sri Lankan Rupee',
                'code'   => 'LKR',
                'symbol' => 'Rs',
                'status' => 2, // Default
            ],
            [
                'name'   => 'United States Dollar',
                'code'   => 'USD',
                'symbol' => '$',
                'status' => 1,
            ],
            [
                'name'   => 'Euro',
                'code'   => 'EUR',
                'symbol' => '€',
                'status' => 1,
            ],
            [
                'name'   => 'British Pound Sterling',
                'code'   => 'GBP',
                'symbol' => '£',
                'status' => 1,
            ],
            [
                'name'   => 'Indian Rupee',
                'code'   => 'INR',
                'symbol' => '₹',
                'status' => 1,
            ],
            [
                'name'   => 'Japanese Yen',
                'code'   => 'JPY',
                'symbol' => '¥',
                'status' => 1,
            ],
            [
                'name'   => 'Australian Dollar',
                'code'   => 'AUD',
                'symbol' => 'A$',
                'status' => 1,
            ],
            [
                'name'   => 'Canadian Dollar',
                'code'   => 'CAD',
                'symbol' => 'C$',
                'status' => 1,
            ],
            [
                'name'   => 'Chinese Yuan',
                'code'   => 'CNY',
                'symbol' => '¥',
                'status' => 1,
            ],
            [
                'name'   => 'Singapore Dollar',
                'code'   => 'SGD',
                'symbol' => 'S$',
                'status' => 1,
            ],
        ]);
    }
}
