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
        Schema::create('stock_expenditure_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_expenditure_id');
            $table->foreignId('product_id');
            $table->double('quantity');
            $table->double('selling_price');
            $table->double('total');
            $table->enum('discount_type', ['amount', 'percentage'])->default('amount');
            $table->double('discount_amount')->default(0);
            $table->string('discount_percentage')->default(0);
            $table->double('grandtotal');
            $table->text('note')->nullable();
            $table->foreignId('inventory_id');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('stock_expenditure_id')->references('id')->on('stock_expenditures')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('inventory_id')->references('id')->on('inventories')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_expenditure_details');
    }
};
