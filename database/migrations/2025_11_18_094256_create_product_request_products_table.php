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
        Schema::create('product_request_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pr_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(0);
            $table->text('remark')->nullable();
            $table->timestamps();
            
            // Temporarily commented out until pr table is created
            // $table->foreign('pr_id')->references('id')->on('pr')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_request_products');
    }
};
