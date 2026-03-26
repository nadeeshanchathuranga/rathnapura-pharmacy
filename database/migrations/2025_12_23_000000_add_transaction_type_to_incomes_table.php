<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->string('transaction_type')->default('sale')->after('payment_type');
        });

        // Backfill existing records to 'sale'
        DB::table('incomes')->whereNull('transaction_type')->update(['transaction_type' => 'sale']);
    }

    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropColumn('transaction_type');
        });
    }
};
