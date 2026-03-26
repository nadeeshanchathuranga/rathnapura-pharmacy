<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add missing quantity columns if they don't exist
            if (!Schema::hasColumn('products', 'shop_quantity_in_transfer_unit')) {
                $table->integer('shop_quantity_in_transfer_unit')->default(0)->after('shop_low_stock_margin')
                    ->comment('Shop stock quantity in transfer units (e.g., bulks/bundles)');
            }
            
            if (!Schema::hasColumn('products', 'store_quantity_in_sale_unit')) {
                $table->integer('store_quantity_in_sale_unit')->default(0)->after('store_quantity_in_transfer_unit')
                    ->comment('Store stock quantity in sales units (e.g., bottles/individual items)');
            }
            
            if (!Schema::hasColumn('products', 'shop_quantity_in_purchase_unit')) {
                $table->integer('shop_quantity_in_purchase_unit')->default(0)->after('shop_quantity_in_sales_unit')
                    ->comment('Shop stock quantity in purchase units (e.g., boxes)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['shop_quantity_in_transfer_unit', 'store_quantity_in_sale_unit', 'shop_quantity_in_purchase_unit']);
        });
    }
};
