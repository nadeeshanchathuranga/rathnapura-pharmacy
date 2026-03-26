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
      Schema::create('sales_return', function (Blueprint $table) {
            $table->id();// Return reference number
            $table->unsignedBigInteger('sale_id')->nullable(); // Related sale
            $table->unsignedBigInteger('customer_id')->nullable(); // Customer who returned
            $table->unsignedBigInteger('user_id'); // Cashier / staff
            $table->date('return_date'); // Date of return
         
            $table->integer('status')->default(0)->comment('0 = Pending, 1 = Approved, 2 = Rejected');
            $table->timestamps();

            // Foreign keys
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return');
    }
};
