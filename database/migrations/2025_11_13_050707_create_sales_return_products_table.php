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
          Schema::create('sales_return_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_return_id'); // Related return
            $table->unsignedBigInteger('product_id'); // Product returned
            $table->integer('quantity'); // Quantity returned
            $table->decimal('price', 15, 2); // Price per unit
            $table->decimal('total', 15, 2); // Line total
            $table->timestamps();

            // Foreign keys
            $table->foreign('sales_return_id')->references('id')->on('sales_return')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_products');
    }
};
