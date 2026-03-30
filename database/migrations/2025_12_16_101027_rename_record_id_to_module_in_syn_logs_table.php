<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('syn_logs')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            if (Schema::hasColumn('syn_logs', 'record_id') && !Schema::hasColumn('syn_logs', 'module')) {
                Schema::table('syn_logs', function ($table) {
                    $table->renameColumn('record_id', 'module');
                });
            }

            return;
        }

        DB::statement('ALTER TABLE syn_logs CHANGE record_id module VARCHAR(255)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('syn_logs')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            if (Schema::hasColumn('syn_logs', 'module') && !Schema::hasColumn('syn_logs', 'record_id')) {
                Schema::table('syn_logs', function ($table) {
                    $table->renameColumn('module', 'record_id');
                });
            }

            return;
        }

        DB::statement('ALTER TABLE syn_logs CHANGE module record_id VARCHAR(255)');
    }
};
