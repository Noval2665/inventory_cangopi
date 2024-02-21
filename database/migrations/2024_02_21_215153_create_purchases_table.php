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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();            
            $table->string('invoice_number');
            $table->date('date');
            $table->double('rupiah_discount')->default(0);
            $table->double('percent_discount')->default(0);
            $table->double('total_purchase')->default(0);
            $table->double('total_price')->default(0);
            $table->double('grand_total')->default(0);
            $table->boolean('status')->default(true);
            $table->foreignId('purchase_order_id');
            $table->foreignId('supplier_id');
            $table->foreignId('user_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
