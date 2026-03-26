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
        Schema::create('product_release_note_produts', function (Blueprint $table) {
            $table->id();
    $table->unsignedBigInteger('product_release_note_id');
    $table->unsignedBigInteger('product_id');
    $table->integer('quantity');
    $table->decimal('unit_price', 15, 2)->nullable();
    $table->decimal('total', 15, 2)->nullable();
    $table->timestamps();

    $table->foreign('product_release_note_id')->references('id')->on('product_release_notes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_release_note_produts');
    }
};
