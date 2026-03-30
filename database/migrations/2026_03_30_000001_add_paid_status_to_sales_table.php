<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'paid_status')) {
                // 0 = Pending (unpaid), 1 = Paid
                $table->tinyInteger('paid_status')->default(0)->after('balance')->comment('0 = Pending, 1 = Paid');
            }
            if (!Schema::hasColumn('sales', 'payment2_type')) {
                // Second payment support: store an optional second payment method and amount
                $table->tinyInteger('payment2_type')->nullable()->after('paid_status')->comment('0=Cash,1=Card,2=Credit');
            }
            if (!Schema::hasColumn('sales', 'payment2_amount')) {
                $table->decimal('payment2_amount', 15, 2)->nullable()->after('payment2_type');
            }
            if (!Schema::hasColumn('sales', 'payment2_card_type')) {
                $table->string('payment2_card_type', 20)->nullable()->after('payment2_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['paid_status', 'payment2_type', 'payment2_amount', 'payment2_card_type']);
        });
    }
};
