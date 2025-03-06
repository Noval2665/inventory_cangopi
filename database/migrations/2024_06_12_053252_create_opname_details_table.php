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
        Schema::create('opname_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opname_id');
            $table->foreignId('product_id');
            $table->double('system_quantity');
            $table->double('physical_quantity');
            $table->double('difference');
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('opname_id')->references('id')->on('opnames')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opname_details');
    }
};
