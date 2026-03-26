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
        Schema::create('store_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('reference_no')->unique()->comment('Unique reference number for inventory record');
            $table->enum('transaction_type', ['adjustment', 'transfer', 'count', 'return'])->default('adjustment');
            $table->decimal('quantity_before', 10, 2)->default(0)->comment('Quantity before transaction');
            $table->decimal('quantity_change', 10, 2)->default(0)->comment('Quantity added/removed');
            $table->decimal('quantity_after', 10, 2)->default(0)->comment('Quantity after transaction');
            $table->string('measurement_unit')->nullable();
            $table->text('remarks')->nullable();
            $table->date('transaction_date');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('completed');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('product_id');
            $table->index('transaction_date');
            $table->index('transaction_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_inventory');
    }
};
