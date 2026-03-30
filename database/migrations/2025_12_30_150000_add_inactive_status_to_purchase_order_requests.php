<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Revert enum back to previous values
        DB::statement("
            ALTER TABLE `purchase_order_requests` 
            MODIFY `status` ENUM('pending','approved','rejected','completed','processing','active') 
            NOT NULL DEFAULT 'pending'
        ");
    }
};
