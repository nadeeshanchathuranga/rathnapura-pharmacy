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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('store_quantity_in_transfer_unit')->default(0)->after('store_quantity_in_purchase_unit')
                ->comment('Store stock quantity in transfer units (e.g., bulks/bundles)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('store_quantity_in_transfer_unit');
        });
    }
};
