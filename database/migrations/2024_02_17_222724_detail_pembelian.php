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
        Schema::create('detail_pembelian', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pembelian');
            $table->unsignedBigInteger('id_produk');
            $table->foreign('id_pembelian')->references('id')->on('pembelian');
            $table->foreign('id_produk')->references('id')->on('produk');
            $table->double('quantity');
            $table->double('received_quantity');
            $table->double('harga_beli');
            $table->enum('tipe_diskon',['Rp','%']);
            $table->double('diskon_rupiah');
            $table->double('diskon_persen');
            $table->double('total');
            $table->text('keterangan');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
