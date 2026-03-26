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
            // Rename batch_id to batch_number
            $table->renameColumn('batch_id', 'batch_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_available_quantities', function (Blueprint $table) {
            // Rename batch_number back to batch_id
            $table->renameColumn('batch_number', 'batch_id');
        });
    }
};
