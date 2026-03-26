<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ShopStockByUnit;
use Illuminate\Support\Facades\DB;

/**
 * Seeder to populate shop_stock_by_unit table with existing shop inventory
 * This assumes that existing shop_quantity_in_sales_unit should be tracked in sales units
 */
class ShopStockByUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Clear existing data
            ShopStockByUnit::truncate();
            
            // Get all products with shop inventory
            $products = Product::where('shop_quantity_in_sales_unit', '>', 0)
                ->with('salesUnit')
                ->get();

            $this->command->info("Found {$products->count()} products with shop inventory.");

            foreach ($products as $product) {
                // Record shop stock in sales unit (the unit it's currently tracked in)
                if ($product->sales_unit_id && $product->shop_quantity_in_sales_unit > 0) {
                    ShopStockByUnit::create([
                        'product_id' => $product->id,
                        'measurement_unit_id' => $product->sales_unit_id,
                        'quantity' => $product->shop_quantity_in_sales_unit
                    ]);
                    
                    $this->command->info("✓ {$product->name}: {$product->shop_quantity_in_sales_unit} {$product->salesUnit->name}");
                }
            }
            
            $this->command->info('');
            $this->command->info('Shop stock by unit seeded successfully!');
            
        } catch (\Exception $e) {
            $this->command->error('Error seeding shop stock by unit: ' . $e->getMessage());
        }
    }
}
