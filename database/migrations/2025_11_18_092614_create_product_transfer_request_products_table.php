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
        Schema::create('product_transfer_request_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_transfer_request_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('requested_quantity')->default(0); 
            $table->string('unit_id')->nullable();
            $table->timestamps();

            $table->foreign('product_transfer_request_id', 'ptr_products_ptr_id_fk')->references('id')->on('product_transfer_requests')->onDelete('cascade');
            $table->foreign('product_id', 'ptr_products_product_id_fk')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_transfer_request_products');
    }
};