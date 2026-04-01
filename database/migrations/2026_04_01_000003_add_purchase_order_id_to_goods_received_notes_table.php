<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add purchase_order_id to goods_received_notes so a GRN can be linked to a PO.
     */
    public function up(): void
    {
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_order_id')->nullable()->after('purchase_order_request_id');
            $table->foreign('purchase_order_id', 'grn_po_id_fk')
                ->references('id')
                ->on('purchase_orders')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropForeign('grn_po_id_fk');
            $table->dropColumn('purchase_order_id');
        });
    }
};
