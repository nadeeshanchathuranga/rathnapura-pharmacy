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
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->decimal('discount_percentage', 8, 2)->nullable()->after('discount_type');
        });

        Schema::table('goods_received_notes_products', function (Blueprint $table) {
            $table->decimal('discount_percentage', 8, 2)->nullable()->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_received_notes_products', function (Blueprint $table) {
            $table->dropColumn('discount_percentage');
        });

        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropColumn('discount_percentage');
        });
    }
};
