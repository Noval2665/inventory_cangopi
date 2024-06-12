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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_return_number', 50);
            $table->foreignId('order_list_id');
            $table->date('date');
            $table->double('total');
            $table->enum('return_type', ['refund', 'change_product']);
            $table->foreignId('user_id');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_list_id')->references('id')->on('order_lists')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
