<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'shift_id')) {
                $table->unsignedBigInteger('shift_id')->nullable()->after('division_id');
                $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
            }

            if (!Schema::hasColumn('sales', 'payment1_type')) {
                $table->tinyInteger('payment1_type')->nullable()->after('paid_status')->comment('0=Cash,1=Card,2=Credit');
            }

            if (!Schema::hasColumn('sales', 'payment1_amount')) {
                $table->decimal('payment1_amount', 15, 2)->nullable()->after('payment1_type');
            }

            if (!Schema::hasColumn('sales', 'payment1_card_type')) {
                $table->string('payment1_card_type', 20)->nullable()->after('payment1_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'shift_id')) {
                $table->dropForeign(['shift_id']);
                $table->dropColumn('shift_id');
            }

            $columns = [];
            if (Schema::hasColumn('sales', 'payment1_type')) {
                $columns[] = 'payment1_type';
            }
            if (Schema::hasColumn('sales', 'payment1_amount')) {
                $columns[] = 'payment1_amount';
            }
            if (Schema::hasColumn('sales', 'payment1_card_type')) {
                $columns[] = 'payment1_card_type';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
