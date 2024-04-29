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
            $table->string('market_list_number', 20);
            $table->date('date');
            $table->foreignId('order_list_id');
            $table->boolean('paid')->default(false);
            $table->text('evidence of transfer')->nullable();
            $table->text('receipt_image')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['Pending', 'Waiting', 'Approve', 'Cancel'])->default('Pending');
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('order_list_id')->references('id')->on('order_lists')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
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
