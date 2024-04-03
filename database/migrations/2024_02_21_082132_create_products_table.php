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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code');
            $table->string('product_name');
            $table->foreignId('brand_id'); //udah
            $table->foreignId('sub_category_id'); //udah(nicxon)
            $table->double('min_stock');
            $table->double('stock'); //in units
            $table->boolean('automatic_use')->default(0);
            $table->double('purchase_price')->default(0);
            $table->double('selling_price')->default(0);
            $table->foreignId('unit_id'); //udah
            $table->double('measurement')->default(0); //in metric
            $table->foreignId('metric_id');
            $table->text('image')->nullable();
            $table->foreignId('storage_id'); //udah
            $table->foreignId('supplier_id');
            $table->enum('product_type', ['raw', 'finished']);
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('deactivated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('sub_category_id')->references('id')->on('sub_categories')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('storage_id')->references('id')->on('storages')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('metric_id')->references('id')->on('metrics')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
