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
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();

            $table->double('quantity')->default(0);
            $table->double('received_quantity')->default(0);
            $table->double('purchase_price')->default(0);
            $table->enum('discount_type', ['Rp', '%']);
            $table->double('rupiah_discount')->default(0);
            $table->double('percent_discount')->default(0);
            $table->double('total')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('purchase_id');
            $table->foreignId('product_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_details');
    }
};
