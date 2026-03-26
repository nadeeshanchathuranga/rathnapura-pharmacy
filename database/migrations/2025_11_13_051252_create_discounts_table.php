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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Discount name (e.g. New Year Offer)
            $table->integer('type')->default(0)->comment('0 = Percentage, 1 = Fixed');
            $table->decimal('value', 10, 2); // Value (e.g. 10 for 10% or 500 for fixed Rs)
            $table->date('start_date')->nullable(); // Valid from
            $table->date('end_date')->nullable(); // Valid till
            $table->boolean('status')->default(1); // 1 = active, 0 = inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
