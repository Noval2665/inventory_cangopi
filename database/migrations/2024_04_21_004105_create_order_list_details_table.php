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
        Schema::create('order_list_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_list_id');
            $table->foreignId('product_id');
            $table->double('quantity');
            $table->double('received_quantity')->default(0);
            $table->double('price')->comment('Harga beli / jual');
            $table->double('total');
            $table->enum('discount_type', ['amount', 'percentage'])->default('amount');
            $table->double('discount_amount')->default(0);
            $table->string('discount_percentage')->default(0);
            $table->double('grandtotal');
            $table->foreignId('description_id');
            $table->foreignId('inventory_id');
            $table->text('note')->nullable();
            $table->enum('purchase_reception_status', ['Ordered', 'Incomplete', 'Complete'])->default('Ordered');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_list_id')->references('id')->on('order_lists')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('inventory_id')->references('id')->on('inventories')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('description_id')->references('id')->on('descriptions')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_list_details');
    }
};
