<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_expenses', function (Blueprint $table) {
            // Update the payment_type column comment to reflect Cheque instead of Credit
            $table->integer('payment_type')->default(0)
                ->comment('0 = Cash, 1 = Card, 2 = Cheque')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_expenses', function (Blueprint $table) {
            // Revert to original comment
            $table->integer('payment_type')->default(0)
                ->comment('0 = Cash, 1 = Card, 2 = Credit')
                ->change();
        });
    }
};
