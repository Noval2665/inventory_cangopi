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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_product_id');
            $table->foreignId('raw_product_id');
            $table->string('portions')->default(0);
            $table->double('measurement')->default(0);
            $table->boolean('is_active')->default(true);
            // $table->foreignId('par_stock_id');
            $table->foreignId('user_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('finished_product_id')->references('id')->on('products')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('raw_product_id')->references('id')->on('products')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            // $table->foreign('par_stock_id')->references('id')->on('products')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
