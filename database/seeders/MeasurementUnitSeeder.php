<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MeasurementUnit;

class MeasurementUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            // Default/Generic
            ['id' => 1, 'name' => 'Unit', 'symbol' => 'UOM', 'status' => '2'],
            
            // Weight Units
            ['id' => 2, 'name' => 'Kilogram', 'symbol' => 'kg', 'status' => '1'],
            ['id' => 3, 'name' => 'Gram', 'symbol' => 'g', 'status' => '1'],
            ['id' => 19, 'name' => 'Ton', 'symbol' => 'ton', 'status' => '1'],
            ['id' => 20, 'name' => 'Pound', 'symbol' => 'lb', 'status' => '1'],
            ['id' => 21, 'name' => 'Ounce', 'symbol' => 'oz', 'status' => '1'],
            
            // Volume Units
            ['id' => 4, 'name' => 'Liter', 'symbol' => 'L', 'status' => '1'],
            ['id' => 5, 'name' => 'Milliliter', 'symbol' => 'mL', 'status' => '1'],
            ['id' => 22, 'name' => 'Gallon', 'symbol' => 'gal', 'status' => '1'],
            ['id' => 23, 'name' => 'Quart', 'symbol' => 'qt', 'status' => '1'],
            ['id' => 24, 'name' => 'Pint', 'symbol' => 'pt', 'status' => '1'],
            
            // Length Units
            ['id' => 6, 'name' => 'Meter', 'symbol' => 'm', 'status' => '1'],
            ['id' => 7, 'name' => 'Centimeter', 'symbol' => 'cm', 'status' => '1'],
            ['id' => 25, 'name' => 'Inch', 'symbol' => 'in', 'status' => '1'],
            ['id' => 26, 'name' => 'Foot', 'symbol' => 'ft', 'status' => '1'],
            
            // Count Units
            ['id' => 8, 'name' => 'Piece', 'symbol' => 'pc', 'status' => '1'],
            ['id' => 10, 'name' => 'Dozen', 'symbol' => 'dz', 'status' => '1'],
            ['id' => 27, 'name' => 'Pair', 'symbol' => 'pr', 'status' => '1'],
            ['id' => 28, 'name' => 'Set', 'symbol' => 'set', 'status' => '1'],
            
            // Container Units (Large - Purchase Units)
            ['id' => 9, 'name' => 'Box', 'symbol' => 'box', 'status' => '1'],
            ['id' => 11, 'name' => 'Carton', 'symbol' => 'ctn', 'status' => '1'],
            ['id' => 12, 'name' => 'Case', 'symbol' => 'case', 'status' => '1'],
            ['id' => 18, 'name' => 'Pallet', 'symbol' => 'plt', 'status' => '1'],
            ['id' => 29, 'name' => 'Crate', 'symbol' => 'crt', 'status' => '1'],
            ['id' => 30, 'name' => 'Sack', 'symbol' => 'sck', 'status' => '1'],
            ['id' => 31, 'name' => 'Container', 'symbol' => 'cont', 'status' => '1'],
            
            // Container Units (Medium - Transfer Units)
            ['id' => 13, 'name' => 'Bulk', 'symbol' => 'bulk', 'status' => '1'],
            ['id' => 16, 'name' => 'Pack', 'symbol' => 'pk', 'status' => '1'],
            ['id' => 17, 'name' => 'Bag', 'symbol' => 'bag', 'status' => '1'],
            ['id' => 32, 'name' => 'Bundle', 'symbol' => 'bdl', 'status' => '1'],
            ['id' => 33, 'name' => 'Tray', 'symbol' => 'tray', 'status' => '1'],
            ['id' => 34, 'name' => 'Roll', 'symbol' => 'roll', 'status' => '1'],
            
            // Container Units (Small - Sales Units)
            ['id' => 14, 'name' => 'Bottle', 'symbol' => 'btl', 'status' => '1'],
            ['id' => 15, 'name' => 'Can', 'symbol' => 'can', 'status' => '1'],
            ['id' => 35, 'name' => 'Jar', 'symbol' => 'jar', 'status' => '1'],
            ['id' => 36, 'name' => 'Tube', 'symbol' => 'tube', 'status' => '1'],
            ['id' => 37, 'name' => 'Pouch', 'symbol' => 'pch', 'status' => '1'],
            ['id' => 38, 'name' => 'Sachet', 'symbol' => 'scht', 'status' => '1'],
            ['id' => 39, 'name' => 'Cup', 'symbol' => 'cup', 'status' => '1'],
            ['id' => 40, 'name' => 'Sheet', 'symbol' => 'sht', 'status' => '1'],
        ];

        foreach ($units as $unit) {
            MeasurementUnit::updateOrCreate(
                ['id' => $unit['id']],
                $unit
            );
        }
    }
}
