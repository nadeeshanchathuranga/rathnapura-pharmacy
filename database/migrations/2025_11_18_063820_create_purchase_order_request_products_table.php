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
        Schema::create('purchase_order_request_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_request_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('requested_quantity')->default(1); 
            $table->integer('issued_quantity')->default(1); 
            $table->foreignId('measurement_unit_id')->nullable()->constrained('measurement_units')->onDelete('set null');
            $table->timestamps(); 
            
            $table->foreign('purchase_order_request_id', 'por_products_por_id_fk')->references('id')->on('purchase_order_requests')->onDelete('cascade');
            $table->foreign('product_id', 'por_products_product_id_fk')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_request_products');
    }
};
