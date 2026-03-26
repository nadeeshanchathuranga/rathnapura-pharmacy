<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'inactive' to the status enum for cancelled purchase orders
        DB::statement("
            ALTER TABLE `purchase_order_requests` 
            MODIFY `status` ENUM('pending','approved','rejected','completed','processing','active','inactive') 
            NOT NULL DEFAULT 'pending'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum back to previous values
        DB::statement("
            ALTER TABLE `purchase_order_requests` 
            MODIFY `status` ENUM('pending','approved','rejected','completed','processing','active') 
            NOT NULL DEFAULT 'pending'
        ");
    }
};
