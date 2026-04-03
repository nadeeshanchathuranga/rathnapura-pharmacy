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
        Schema::create('pre_billing_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_type')->default('retail');
            $table->date('sale_date')->nullable();
            $table->decimal('discount', 12, 2)->default(0);
            $table->json('items');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['division_id', 'created_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_billing_tokens');
    }
};
