<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Type;

class TypeSeeder extends Seeder
{
    public function run(): void
    {
        Type::insert([
    [
        'id' => 1,
        'name' => 'Default Type',
        'status' => '2',
    ],
    [
        'id' => 2,
        'name' => 'Physical Product',
        'status' => '1',
    ],
    [
        'id' => 3,
        'name' => 'Digital Product',
        'status' => '1',
    ],
    [
        'id' => 4,
        'name' => 'Service',
        'status' => '1',
    ],
    [
        'id' => 5,
        'name' => 'Subscription',
        'status' => '1',
    ],
    [
        'id' => 6,
        'name' => 'Bundle',
        'status' => '1',
    ],
    [
        'id' => 7,
        'name' => 'Raw Material',
        'status' => '1',
    ],
    [
        'id' => 8,
        'name' => 'Consumable',
        'status' => '1',
    ],
    [
        'id' => 9,
        'name' => 'Accessory',
        'status' => '1',
    ],
    [
        'id' => 10,
        'name' => 'Gift Item',
        'status' => '1',
    ],
]);

    }
}
