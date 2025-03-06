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
            $table->string('par_stock_number', 20)->unique();
            $table->date('date');
            $table->foreignId('inventory_id');
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
        Schema::dropIfExists('par_stocks');
    }
};
