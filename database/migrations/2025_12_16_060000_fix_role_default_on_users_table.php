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
        // If the users table has a role column, ensure it is unsigned tinyint NOT NULL DEFAULT 0
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            // Use raw SQL to avoid requiring doctrine/dbal for column modification
            DB::statement("ALTER TABLE `users` MODIFY `role` TINYINT UNSIGNED NOT NULL DEFAULT 0;");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            // Revert to nullable without default
            DB::statement("ALTER TABLE `users` MODIFY `role` TINYINT UNSIGNED NULL DEFAULT NULL;");
        }
    }
};
