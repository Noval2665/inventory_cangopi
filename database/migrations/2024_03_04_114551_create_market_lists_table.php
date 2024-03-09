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
        Schema::create('market_lists', function (Blueprint $table) {
            $table->id();
            $table->string('market_list_code');
            $table->string('market_list_name');
            $table->enum('status', ['Pending', 'Approve', 'Cancel', 'Waiting'])->default('Pending');
            $table->date('date');
            $table->text('explanation')->nullable(); //exclusive finance
            $table->string('receipt_image')->nullable(); //exclusive finance
            $table->foreignId('user_id');
            $table->foreignId('order_list_id'); #ambil tanggal juga dari ini

            //receipt image itu foto struk pas di approve dan diturunin dana finance

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('order_list_id')->references('id')->on('order_lists')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_lists');
    }
};
