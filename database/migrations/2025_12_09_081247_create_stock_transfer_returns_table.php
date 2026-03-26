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
        Schema::create('stock_transfer_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique()->comment('Return reference number');
            $table->unsignedBigInteger('user_id')->comment('User who processed the return');
            $table->unsignedBigInteger('product_id')->comment('Product being returned');
            $table->decimal('stock_transfer_quantity', 10, 2)->comment('Quantity returned from shop to store');
            $table->text('reason')->nullable()->comment('Reason for return (e.g., damaged, expired)');
            $table->enum('status', ['pending', 'approved', 'completed'])->default('pending');
            $table->date('return_date');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_returns');
    }
};
