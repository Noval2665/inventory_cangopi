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
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('detail_pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pengeluaran');
            $table->unsignedBigInteger('id_produk');
            $table->unsignedBigInteger('id_description');
            $table->foreign('id_pengeluaran')->references('id')->on('pengeluaran');
            $table->foreign('id_produk')->references('id')->on('produk');
            $table->foreign('id_description')->references('id')->on('description');
            $table->double('jumlah');
            $table->double('harga');
            $table->double('total_pengeluaran');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
