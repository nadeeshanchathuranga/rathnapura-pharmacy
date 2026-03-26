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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');           
            $table->string('barcode')->unique()->nullable()->comment('Auto-generated barcode');
            $table->foreignId('brand_id')->nullable();
            $table->foreignId('category_id')->nullable();            
            $table->foreignId('type_id')->nullable(); 
            $table->foreignId('discount_id')->nullable();
            $table->foreignId('tax_id')->nullable();    
            $table->integer('shop_quantity')->default(0)->comment('Shop stock quantity');
            $table->integer('shop_low_stock_margin')->default(0)->comment('Shop low stock margin alert');
            

            
            $table->integer('store_quantity')->default(0)->comment('Store stock quantity');
            $table->integer('store_low_stock_margin')->default(0)->comment('Store low stock margin alert');                         
             
           

            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('wholesale_price', 10, 2)->nullable();
            $table->decimal('retail_price', 10, 2)->nullable();          
            $table->boolean('return_product')->default(false)->comment('0 = No, 1 = Yes');
            $table->string('purchase_unit_id')->nullable();
            $table->string('sales_unit_id')->nullable();
            $table->string('transfer_unit_id')->nullable();
            $table->decimal('purchase_to_transfer_rate', 10, 2)->default(1)->comment('How many transfer units per purchase unit')->nullable();
            $table->decimal('transfer_to_sales_rate', 10, 2)->default(1)->comment('How many sales units per transfer unit')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0 = Inactive, 1 = Active, 2 = Default');
            $table->string('image')->nullable();
            $table->timestamps();

        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
