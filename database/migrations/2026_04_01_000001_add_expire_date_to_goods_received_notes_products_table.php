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
        Schema::table('goods_received_notes_products', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_received_notes_products', 'expire_date')) {
                $table->date('expire_date')->nullable()->after('batch_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_received_notes_products', function (Blueprint $table) {
            if (Schema::hasColumn('goods_received_notes_products', 'expire_date')) {
                $table->dropColumn('expire_date');
            }
        });
    }
};
