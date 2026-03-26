<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Rename columns for clarity in multi-level unit system
            $table->renameColumn('store_quantity', 'store_quantity_in_purchase_unit');
            $table->renameColumn('shop_quantity', 'shop_quantity_in_sales_unit');
        });
        
        // Update column comments
        DB::statement("ALTER TABLE products MODIFY COLUMN store_quantity_in_purchase_unit INT DEFAULT 0 COMMENT 'Store stock quantity in purchase units (e.g., boxes)'");
        DB::statement("ALTER TABLE products MODIFY COLUMN shop_quantity_in_sales_unit INT DEFAULT 0 COMMENT 'Shop stock quantity in sales units (e.g., bottles)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Revert column names
            $table->renameColumn('store_quantity_in_purchase_unit', 'store_quantity');
            $table->renameColumn('shop_quantity_in_sales_unit', 'shop_quantity');
        });
        
        // Revert column comments
        DB::statement("ALTER TABLE products MODIFY COLUMN store_quantity INT DEFAULT 0 COMMENT 'Store stock quantity'");
        DB::statement("ALTER TABLE products MODIFY COLUMN shop_quantity INT DEFAULT 0 COMMENT 'Shop stock quantity'");
    }
};
