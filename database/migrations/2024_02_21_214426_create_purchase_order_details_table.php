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
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_order_number');
            $table->date('date');
            $table->double('total_purchase')->default(0);
            $table->double('total_price')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('purchase_order_id');
            $table->foreignId('product_id');
            $table->foreignId('description_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('description_id')->references('id')->on('descriptions')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_details');
    }
};
