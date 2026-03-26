<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds discount tracking at the item level to support
     * accurate calculations for partial returns with discounts.
     */
    public function up(): void
    {
        Schema::table('sales_products', function (Blueprint $table) {
            // Store the discount amount allocated to this line item (proportional)
            $table->decimal('discount_amount', 15, 2)->default(0)->after('total')
                ->comment('Proportional discount allocated to this line item');
            
            // Store the net amount after applying discount (total - discount_amount)
            $table->decimal('net_amount', 15, 2)->default(0)->after('discount_amount')
                ->comment('Line total after discount (total - discount_amount)');
            
            // Track if this is a return (negative quantity)
            $table->boolean('is_return')->default(false)->after('net_amount')
                ->comment('True if this is a return/refund line item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_products', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'net_amount', 'is_return']);
        });
    }
};
