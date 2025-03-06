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
        Schema::create('product_out_details', function (Blueprint $table) {
            $table->id();
            $table->double('total_quantity')->default(0);
            $table->double('total_price')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('product_out_id');
            $table->foreignId('description_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('product_out_id')->references('id')->on('product_outs')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('description_id')->references('id')->on('descriptions')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_out_details');
    }
};
