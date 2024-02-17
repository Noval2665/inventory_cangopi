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
        Schema::create('pembelian', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_purchase_order');
            $table->unsignedBigInteger('id_supplier');
            $table->unsignedBigInteger('id_user');
            $table->foreign('id_purchase_order')->references('id')->on('purchase_order');
            $table->foreign('id_supplier')->references('id')->on('supplier');
            $table->foreign('id_user')->references('id')->on('user');
            $table->string('no_invoice');
            $table->date('tanggal');
            $table->double('diskon_rupiah');
            $table->double('diskon_persen');
            $table->double('total_pembelian');
            $table->double('total_harga');
            $table->double('grand_total');
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
