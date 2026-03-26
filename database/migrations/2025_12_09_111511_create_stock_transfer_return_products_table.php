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
        Schema::create('stock_transfer_return_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_return_id')->constrained('stock_transfer_returns')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('measurement_unit_id')->constrained('measurement_units')->onDelete('cascade');
            $table->integer('stock_transfer_quantity');
            $table->timestamps();
        });

        // Drop columns from stock_transfer_returns table
        Schema::table('stock_transfer_returns', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'stock_transfer_quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore columns to stock_transfer_returns table
        Schema::table('stock_transfer_returns', function (Blueprint $table) {
            $table->foreignId('product_id')->after('user_id')->constrained('products')->onDelete('cascade');
            $table->integer('stock_transfer_quantity')->after('product_id');
        });

        Schema::dropIfExists('stock_transfer_return_products');
    }
};
