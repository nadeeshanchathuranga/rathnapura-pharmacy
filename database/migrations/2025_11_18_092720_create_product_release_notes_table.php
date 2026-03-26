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
        Schema::create('product_release_notes', function (Blueprint $table) {
            $table->id();
             $table->unsignedBigInteger('product_transfer_request_id')->nullable()->comment('Product Transfer Request ID');
            $table->unsignedBigInteger('user_id')->comment('Released by user');
        
            $table->date('release_date');
            $table->tinyInteger('status')->default(0)->comment('0 = Pending, 1 = Released');
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_release_notes');
    }
};
