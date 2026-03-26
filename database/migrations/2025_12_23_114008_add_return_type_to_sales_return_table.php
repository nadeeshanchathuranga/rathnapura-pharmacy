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
        Schema::table('sales_return', function (Blueprint $table) {
            // Add return_type: 1 = Product Return (return to inventory), 2 = Cash Return (refund money)
            $table->tinyInteger('return_type')->default(1)->after('return_date')
                ->comment('1 = Product Return (stock), 2 = Cash Return (refund)');
            
            // Add refund_amount for cash returns
            $table->decimal('refund_amount', 15, 2)->nullable()->after('return_type')
                ->comment('Total refund amount for cash returns');
            
            // Add payment method for cash returns
            $table->string('refund_method')->nullable()->after('refund_amount')
                ->comment('Cash, Card, Mobile Money, etc.');
            
            // Add notes/reason for return
            $table->text('notes')->nullable()->after('refund_method')
                ->comment('Reason or notes for the return');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_return', function (Blueprint $table) {
            $table->dropColumn(['return_type', 'refund_amount', 'refund_method', 'notes']);
        });
    }
};
