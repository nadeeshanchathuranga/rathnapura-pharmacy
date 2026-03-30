<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'start_note')) {
                $table->string('start_note', 500)->nullable()->after('opening_till_amount');
            }

            if (!Schema::hasColumn('shifts', 'end_note')) {
                $table->string('end_note', 500)->nullable()->after('closing_cash_amount');
            }

            if (!Schema::hasColumn('shifts', 'total_sales')) {
                $table->decimal('total_sales', 15, 2)->nullable()->after('expected_closing_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('shifts', 'start_note')) {
                $columns[] = 'start_note';
            }

            if (Schema::hasColumn('shifts', 'end_note')) {
                $columns[] = 'end_note';
            }

            if (Schema::hasColumn('shifts', 'total_sales')) {
                $columns[] = 'total_sales';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
