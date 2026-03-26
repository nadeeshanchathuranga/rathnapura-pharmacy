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
        Schema::dropIfExists('module_settings');
    }

    /**
     * down the migrations.
     */
    public function down(): void
    {
        // Cannot recreate the table structure in reverse
        // Manual restoration required if needed
    }
};
