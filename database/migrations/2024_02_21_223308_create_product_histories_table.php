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
        Schema::create('product_histories', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->double('quantity')->default(0);
            $table->double('purchase_price')->default(0);
            $table->double('selling_price')->default(0);
            $table->enum('discount_type', ['amount', 'percentage'])->default('amount');
            $table->double('discount_amount')->default(0);
            $table->double('discount_percentage')->default(0);
            $table->double('total')->default(0);
            $table->double('remaining_stock')->default(0);
            $table->string('reference_number');
            $table->string('category');
            $table->enum('type', ['IN', 'OUT']);
            $table->string('product_history_reference');

            $table->foreignId('user_id');
            $table->foreignId('product_id');
            $table->foreignId('inventory_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_histories');
    }
};
