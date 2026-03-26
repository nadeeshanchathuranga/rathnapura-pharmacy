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
        // Add 'active' to the status enum
        DB::statement("
            ALTER TABLE `purchase_order_requests` 
            MODIFY `status` ENUM('pending','approved','rejected','completed','processing','active') 
            NOT NULL DEFAULT 'active'
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
            MODIFY `status` ENUM('pending','approved','rejected','completed','processing') 
            NOT NULL DEFAULT 'pending'
        ");
    }
};
