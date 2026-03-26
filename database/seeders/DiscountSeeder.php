<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Discount;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        Discount::insert([
    [
        'id' => 1,
        'name' => 'No Discount',
        'type' => '0', // 0 = Fixed / None
        'value' => 0,
        'start_date' => null,
        'end_date' => null,
        'status' => '2',
    ],
    [
        'id' => 2,
        'name' => 'New Year Offer',
        'type' => '1', // Percentage
        'value' => 10,
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-31',
        'status' => '1',
    ],
    [
        'id' => 3,
        'name' => 'Festival Sale',
        'type' => '1',
        'value' => 15,
        'start_date' => '2025-03-01',
        'end_date' => '2025-03-10',
        'status' => '1',
    ],
    [
        'id' => 4,
        'name' => 'Clearance Discount',
        'type' => '0', // Fixed amount
        'value' => 500,
        'start_date' => null,
        'end_date' => null,
        'status' => '1',
    ],
    [
        'id' => 5,
        'name' => 'Black Friday Deal',
        'type' => '1',
        'value' => 25,
        'start_date' => '2025-11-25',
        'end_date' => '2025-11-30',
        'status' => '1',
    ],
    [
        'id' => 6,
        'name' => 'Loyal Customer Discount',
        'type' => '1',
        'value' => 5,
        'start_date' => null,
        'end_date' => null,
        'status' => '1',
    ],
    [
        'id' => 7,
        'name' => 'Bulk Purchase Offer',
        'type' => '0',
        'value' => 1000,
        'start_date' => null,
        'end_date' => null,
        'status' => '1',
    ],
    [
        'id' => 8,
        'name' => 'Seasonal Sale',
        'type' => '1',
        'value' => 20,
        'start_date' => '2025-06-01',
        'end_date' => '2025-06-30',
        'status' => '1',
    ],
    [
        'id' => 9,
        'name' => 'Flash Sale',
        'type' => '1',
        'value' => 30,
        'start_date' => '2025-08-15',
        'end_date' => '2025-08-16',
        'status' => '1',
    ],
    [
        'id' => 10,
        'name' => 'Referral Discount',
        'type' => '0',
        'value' => 200,
        'start_date' => null,
        'end_date' => null,
        'status' => '1',
    ],
]);

    }
}
