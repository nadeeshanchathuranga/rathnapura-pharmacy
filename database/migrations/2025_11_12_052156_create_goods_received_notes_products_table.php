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
         Schema::create('goods_received_notes_products', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('goods_received_note_id');
        $table->unsignedBigInteger('product_id');
        $table->integer('quantity')->default(0);
        $table->decimal('purchase_price', 10, 2)->nullable();
        $table->decimal('discount', 10, 2)->nullable();
        $table->decimal('total', 15, 2)->default(0);
        $table->timestamps();

        $table->foreign('goods_received_note_id')->references('id')->on('goods_received_notes')->onDelete('cascade');
        $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_received_notes_products');
    }
};
