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
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_request_id')->nullable()->comment('Purchase Order ID');
            $table->string('goods_received_note_no')->unique()->comment('GRN reference number');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Created by user');
            $table->date('goods_received_note_date')->nullable(); 
            $table->decimal('discount', 10, 2)->default(0); 
            $table->decimal('tax_total', 10, 2)->default(0); 
            $table->text('remarks')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0 = Inactive, 1 = Active, 2 = Default');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_received_notes');
    }
};
