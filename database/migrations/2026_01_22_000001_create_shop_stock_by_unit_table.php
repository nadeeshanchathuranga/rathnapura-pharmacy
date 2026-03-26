<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table tracks shop inventory by specific measurement units.
     * When a shop receives a transfer in bottles, we record bottles.
     * When returning stock, we deduct from the exact unit held in the shop.
     */
    public function up(): void
    {
        Schema::create('shop_stock_by_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('measurement_unit_id')->constrained('measurement_units')->onDelete('cascade');
            $table->decimal('quantity', 15, 2)->default(0)->comment('Quantity in the specific unit');
            $table->timestamps();

            // Composite unique index: one record per product-unit combination
            $table->unique(['product_id', 'measurement_unit_id'], 'product_unit_unique');
            
            // Indexes for faster lookups
            $table->index('product_id');
            $table->index('measurement_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_stock_by_unit');
    }
};
