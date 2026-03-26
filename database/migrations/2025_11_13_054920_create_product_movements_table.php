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
      Schema::create('product_movements', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('product_id');
    $table->integer('movement_type')
                ->default(0)
                ->comment('0 = Purchase, 1 = Purchase Return, 2 = Transfer, 3 = Sale, 4 = Sale Return, 5 = BRN Return');
    $table->decimal('quantity', 10, 2);           // Quantity moved
    $table->string('reference')->nullable();      // Optional: invoice / GRN / etc.
    $table->timestamps();

    // Foreign key
    $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_movements');
    }
};
