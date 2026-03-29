<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            [
                'name' => 'Pharmacy',
                'slug' => 'pharmacy',
                'description' => 'Pharmacy division - medicines and healthcare products',
                'status' => true,
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'description' => 'Sports division - sports equipment and accessories',
                'status' => true,
            ],
        ];

        foreach ($divisions as $division) {
            Division::firstOrCreate(['slug' => $division['slug']], $division);
        }
    }
}
