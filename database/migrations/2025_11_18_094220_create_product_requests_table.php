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
        Schema::create('product_requests', function (Blueprint $table) {
            $table->id();
$table->unsignedBigInteger('goods_received_note_id');
$table->date('return_date');
$table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
$table->timestamps();
$table->foreign('goods_received_note_id')->references('id')->on('goods_received_notes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_requests');
    }
};
