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
        Schema::create('order_lists', function (Blueprint $table) {
            $table->id();
            $table->string('order_list_number', 50);
            $table->date('date');
            $table->double('total');
            $table->enum('discount_type', ['amount', 'percentage'])->default('amount');
            $table->double('discount_amount')->default(0);
            $table->double('discount_percentage')->default(0);
            $table->double('grandtotal');
            $table->foreignId('description_id')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['pending', 'waiting', 'approved', 'rejected'])->default('pending');
            $table->foreignId('user_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('description_id')->references('id')->on('descriptions')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_lists');
    }
};
