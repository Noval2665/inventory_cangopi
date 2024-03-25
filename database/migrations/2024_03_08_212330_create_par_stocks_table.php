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
        Schema::create('par_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('ready_stock');
            $table->string('in_stock');
            $table->string('end_stock');
            $table->enum('product_status', ['opened', 'unopened'])->default('unopened');
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id');
            $table->foreignId('par_stock_product_id');
            $table->foreignId('sales_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('par_stock_product_id')->references('id')->on('par_stock_products')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('par_stocks');
    }
};
