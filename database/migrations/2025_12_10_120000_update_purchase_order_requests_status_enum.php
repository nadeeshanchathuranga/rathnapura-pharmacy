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
        // 1. First alter the enum to allow new values
            // Add 'processing' to the status enum while preserving existing values
            DB::statement("
                ALTER TABLE `purchase_order_requests` MODIFY `status` ENUM('pending','approved','rejected','completed','processing') NOT NULL DEFAULT 'pending'
            ");

        // No automatic status migration here — we only extend the enum so application-level
        // logic can set 'processing' when a GRN is created.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum back to original values
        DB::statement("ALTER TABLE `purchase_order_requests` MODIFY `status` ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending'");
    }
};
