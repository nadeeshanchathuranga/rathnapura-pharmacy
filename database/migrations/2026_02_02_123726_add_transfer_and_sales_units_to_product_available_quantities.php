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
        Schema::table('product_available_quantities', function (Blueprint $table) {
            $table->decimal('quantity_in_transfer_unit', 12, 2)->default(0)->comment('Quantity in transfer units (bundles)');
            $table->decimal('quantity_in_sales_unit', 12, 2)->default(0)->comment('Quantity in sales units (individual items)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_available_quantities', function (Blueprint $table) {
            $table->dropColumn(['quantity_in_transfer_unit', 'quantity_in_sales_unit']);
        });
    }
};
