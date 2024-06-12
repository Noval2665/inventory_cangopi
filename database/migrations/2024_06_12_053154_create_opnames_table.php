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
        Schema::create('opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number', 50);
            $table->date('date');
            $table->foreignId('inventory_id');
            $table->text('description')->nullable();
            $table->foreignId('user_id');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opnames');
    }
};
