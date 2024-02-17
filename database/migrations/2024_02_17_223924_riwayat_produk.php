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
        Schema::create('riwayat_produk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_produk');
            $table->foreign('id_user')->references('id')->on('user');
            $table->foreign('id_produk')->references('id')->on('produk');
            $table->date('tanggal');
            $table->double('jumlah');
            $table->double('harga');
            $table->integer('id_transaksi');
            $table->string('no_transaksi');
            $table->string('jenis_transaksi');
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
