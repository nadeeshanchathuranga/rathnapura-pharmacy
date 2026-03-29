<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'division_id')) {
                $table->unsignedBigInteger('division_id')->nullable()->after('role');
                $table->foreign('division_id')->references('id')->on('divisions')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('division_id');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'division_id')) {
                $table->unsignedBigInteger('division_id')->nullable()->after('status');
                $table->foreign('division_id')->references('id')->on('divisions')->nullOnDelete();
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'division_id')) {
                $table->unsignedBigInteger('division_id')->nullable()->after('user_id');
                $table->foreign('division_id')->references('id')->on('divisions')->nullOnDelete();
            }
        });

        Schema::table('goods_received_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_received_notes', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved'])->default('pending')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropColumn('approval_status');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
            $table->dropColumn('is_active');
        });
    }
};
