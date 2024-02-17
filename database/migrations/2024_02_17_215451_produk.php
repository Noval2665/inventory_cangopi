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
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_sub_kategori');
            $table->unsignedBigInteger('id_storage');
            $table->unsignedBigInteger('id_merek');
            $table->unsignedBigInteger('id_satuan');
            $table->unsignedBigInteger('id_metric');
            $table->unsignedBigInteger('id_supplier');
            $table->foreign('id_user')->references('id')->on('user');
            $table->foreign('id_sub_kategori')->references('id')->on('sub_kategori');
            $table->foreign('id_storage')->references('id')->on('storage');
            $table->foreign('id_merek')->references('id')->on('merek');
            $table->foreign('id_satuan')->references('id')->on('satuan');
            $table->foreign('id_metric')->references('id')->on('metric');
            $table->foreign('id_supplier')->references('id')->on('supplier');
            $table->string('nama_produk');
            $table->double('harga_beli');
            $table->double('stok');
            $table->double('size');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('image');
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
