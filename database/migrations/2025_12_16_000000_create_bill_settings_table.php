<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_settings', function (Blueprint $table) {
            $table->id();
            $table->string('logo_path')->nullable();
            $table->string('company_name');
            $table->string('address');
            $table->string('mobile_1');
            $table->string('mobile_2')->nullable();
            $table->string('email')->nullable();
            $table->string('website_url')->nullable();
            $table->text('footer_description')->nullable();
            $table->enum('print_size', ['58mm', '80mm', '112mm', '210mm'])->default('80mm');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_settings');
    }
};
