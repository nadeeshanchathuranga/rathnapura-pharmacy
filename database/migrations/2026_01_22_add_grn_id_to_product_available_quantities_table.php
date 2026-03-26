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
        Schema::table('product_available_quantities', function (Blueprint $table) {
            $table->unsignedBigInteger('goods_received_note_id')->nullable()->after('unit_id');
            $table->foreign('goods_received_note_id')->references('id')->on('goods_received_notes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_available_quantities', function (Blueprint $table) {
            $table->dropForeign(['goods_received_note_id']);
            $table->dropColumn('goods_received_note_id');
        });
    }
};
