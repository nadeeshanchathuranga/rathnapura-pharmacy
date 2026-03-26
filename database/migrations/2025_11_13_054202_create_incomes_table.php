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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->string('source');    
            $table->unsignedBigInteger('sale_id')->nullable(); // Related sale (optional)
            $table->decimal('amount', 10, 2);    // Amount of income
            $table->date('income_date');          // Date of income
            $table->integer('payment_type')->default(0)->comment('0 = Cash, 1 = Card, 2 = Credit'); // Payment type
            $table->timestamps(); 
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
