<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::insert([
        [
            'id' => 1,
            'name' => 'Default Category',
            'parent_id' => null,
            'status' => '2',
        ],
        [
            'id' => 2,
            'name' => 'Electronics',
            'parent_id' => 1,
            'status' => '1',
        ],
        [
            'id' => 3,
            'name' => 'Fashion',
            'parent_id' => 1,
            'status' => '1',
        ],
        [
            'id' => 4,
            'name' => 'Home & Kitchen',
            'parent_id' => 1,
            'status' => '1',
        ],
        [
            'id' => 5,
            'name' => 'Books',
            'parent_id' => 1,
            'status' => '1',
        ],
        [
            'id' => 6,
            'name' => 'Sports',
            'parent_id' => 1,
            'status' => '1',
        ],
        [
            'id' => 7,
            'name' => 'Beauty',
            'parent_id' => 1,
            'status' => '1',
        ],
        [
            'id' => 8,
            'name' => 'Toys',
            'parent_id' => 1,
            'status' => '1',
        ],
        [
            'id' => 9,
            'name' => 'Automotive',
            'parent_id' => 1,
            'status' => '1',
        ],
        [
            'id' => 10,
            'name' => 'Groceries',
            'parent_id' => 1,
            'status' => '1',
        ],
    ]);

    }
}
