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
            $table->string('par_stock_code');
            $table->string('par_stock_name');
            $table->double('minimum_stock')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id');
            $table->foreignId('metric_id');
            $table->foreignId('storage_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('metric_id')->references('id')->on('units')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('storage_id')->references('id')->on('storages')->onDelete('restrict')->onUpdate('cascade');
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
