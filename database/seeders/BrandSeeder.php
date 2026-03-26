<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
    
        Brand::insert([
    [
        'id' => 1,
        'name' => 'Default Brand',
        'status' => '2',
    ],
    [
        'id' => 2,
        'name' => 'Premium Brand',
        'status' => '1',
    ],
    [
        'id' => 3,
        'name' => 'Eco Brand',
        'status' => '1',
    ],
    [
        'id' => 4,
        'name' => 'Luxury Brand',
        'status' => '1',
    ],
    [
        'id' => 5,
        'name' => 'Budget Brand',
        'status' => '1',
    ],
    [
        'id' => 6,
        'name' => 'Global Brand',
        'status' => '1',
    ],
    [
        'id' => 7,
        'name' => 'Local Brand',
        'status' => '1',
    ],
    [
        'id' => 8,
        'name' => 'Digital Brand',
        'status' => '1',
    ],
    [
        'id' => 9,
        'name' => 'Fashion Brand',
        'status' => '1',
    ],
    [
        'id' => 10,
        'name' => 'Tech Brand',
        'status' => '1',
    ],
]);

 
         
    }
}
