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
        // Check if grn_returns table exists, then rename it
        if (Schema::hasTable('grn_returns')) {
            Schema::rename('grn_returns', 'goods_received_note_returns');
        }

        // Rename grn_id column to goods_received_note_id if it exists
        if (Schema::hasColumn('goods_received_note_returns', 'grn_id')) {
            Schema::table('goods_received_note_returns', function (Blueprint $table) {
                $table->renameColumn('grn_id', 'goods_received_note_id');
            });
        }

        // Check if grn_return_products table exists, then rename it
        if (Schema::hasTable('grn_return_products')) {
            Schema::rename('grn_return_products', 'goods_received_note_return_products');
        }

        // Rename grn_return_id column if it exists
        if (Schema::hasColumn('goods_received_note_return_products', 'grn_return_id')) {
            Schema::table('goods_received_note_return_products', function (Blueprint $table) {
                $table->renameColumn('grn_return_id', 'goods_received_note_return_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the column rename in goods_received_note_return_products
        if (Schema::hasColumn('goods_received_note_return_products', 'goods_received_note_return_id')) {
            Schema::table('goods_received_note_return_products', function (Blueprint $table) {
                $table->renameColumn('goods_received_note_return_id', 'grn_return_id');
            });
        }

        // Reverse the table rename
        if (Schema::hasTable('goods_received_note_return_products')) {
            Schema::rename('goods_received_note_return_products', 'grn_return_products');
        }

        // Reverse the column rename in goods_received_note_returns
        if (Schema::hasColumn('goods_received_note_returns', 'goods_received_note_id')) {
            Schema::table('goods_received_note_returns', function (Blueprint $table) {
                $table->renameColumn('goods_received_note_id', 'grn_id');
            });
        }

        // Reverse the table rename
        if (Schema::hasTable('goods_received_note_returns')) {
            Schema::rename('goods_received_note_returns', 'grn_returns');
        }
    }
};
