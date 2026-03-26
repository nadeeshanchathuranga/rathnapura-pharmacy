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
        Schema::dropIfExists('product_requests');
        Schema::dropIfExists('product_requests_product');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::create('product_requests', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('product_requests_product', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};
