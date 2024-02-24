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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_order_number');
            $table->date('date');
            $table->double('total_purchase')->default(0);
            $table->double('total_price')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id');
            $table->foreignId('supplier_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
