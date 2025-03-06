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
        Schema::create('par_stock_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('par_stock_id');
            $table->foreignId('product_id');
            $table->double('system_stock');
            $table->double('physical_stock');
            $table->double('difference');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('par_stock_id')->references('id')->on('par_stocks')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('par_stock_details');
    }
};
