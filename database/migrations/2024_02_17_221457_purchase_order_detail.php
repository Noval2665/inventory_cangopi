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
        Schema::create('purchase_order_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_purchase_order');
            $table->unsignedBigInteger('id_produk');
            $table->unsignedBigInteger('id_description');
            $table->foreign('id_purchase_order')->references('id')->on('purchase_order');
            $table->foreign('id_produk')->references('id')->on('produk');
            $table->foreign('id_description')->references('id')->on('description');
            $table->string('no_purchase_order');
            $table->date('tanggal');
            $table->double('total_pembelian');
            $table->double('total_harga');
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
