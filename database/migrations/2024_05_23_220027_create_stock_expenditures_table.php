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
        Schema::create('stock_expenditures', function (Blueprint $table) {
            $table->id();
            $table->string('stock_expenditure_number', 20)->unique();
            $table->date('date');
            $table->double('total');
            $table->enum('discount_type', ['amount', 'percentage'])->default('amount');
            $table->double('discount_amount')->default(0);
            $table->double('discount_percentage')->default(0);
            $table->double('ppn_percentage')->default(0);
            $table->double('grandtotal');
            $table->foreignId('inventory_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('user_id');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inventory_id')->references('id')->on('inventories')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_expenditures');
    }
};
