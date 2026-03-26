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
       Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique(); // Invoice number
            $table->unsignedBigInteger('customer_id')->nullable(); // Optional customer
            $table->unsignedBigInteger('user_id'); // Cashier / user who made sale
            $table->decimal('total_amount', 15, 2); // Total before discount
            $table->decimal('discount', 15, 2)->default(0); // Discount applied
            $table->decimal('net_amount', 15, 2); // Total after discount
             $table->decimal('balance', 15, 2)->default(0); // Remaining balance
             $table->date('sale_date'); // Sale date
            $table->integer('type')->comment('1 = Retail, 2 = Wholesale');


            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
