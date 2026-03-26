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
         Schema::create('syn_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');     // Table that was synced
            $table->text('record_id');        // ID of the record
            $table->string('action');         // Action type: insert, update, delete
            $table->timestamp('synced_at')->nullable(); // Time synced
            $table->unsignedBigInteger('user_id')->nullable(); // POS user who performed action
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syn_logs');
    }
};
