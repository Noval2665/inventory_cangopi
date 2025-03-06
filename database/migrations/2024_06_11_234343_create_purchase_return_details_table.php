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
        Schema::create('purchase_return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id');
            $table->foreignId('product_id');
            $table->double('quantity');
            $table->double('purchase_price');
            $table->enum('discount_type', ['amount', 'percentage'])->default('amount');
            $table->double('discount_amount')->default(0);
            $table->string('discount_percentage')->default(0);
            $table->double('total');
            $table->text('reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_details');
    }
};
