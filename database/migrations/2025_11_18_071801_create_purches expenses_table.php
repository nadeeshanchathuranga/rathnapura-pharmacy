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
        Schema::create('purchase_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Expense title
            $table->decimal('amount', 15, 2); // Expense amount
            $table->text('remark')->nullable(); // Optional description
            $table->date('expense_date'); // Expense date
            $table->integer('payment_type')->default(0)
           ->comment('0 = Cash, 1 = Card, 2 = Credit');
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('user_id'); // Who added the expense
               $table->unsignedBigInteger('supplier_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_expenses');
    }
};
