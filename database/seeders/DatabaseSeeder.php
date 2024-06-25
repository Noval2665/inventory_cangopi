<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Description;
use App\Models\Inventory;
use App\Models\Metric;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductIn;
use App\Models\ProductInfo;
use App\Models\Role;
use App\Models\Storage;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Symfony\Component\Console\Descriptor\Descriptor;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $roleSuperAdmin = Role::create([
            'name' => 'Superadmin',
            'permissions' => [
                [
                    "parent" => "all",
                    "action" => "manage",
                    "subject" => "all"
                ]
            ]
        ]);

        $roleAdmin = Role::create([
            'name' => 'Admin',
            'permissions' => [
                [
                    "parent" => "all",
                    "path" => "all",
                    "action" => "manage",
                    "subject" => "all"
                ]
            ]
        ]);

        $roleFinance = Role::create([
            'name' => 'Finance',
            'permissions' => [
                [
                    "parent" => "all",
                    "path" => "all",
                    "action" => "manage",
                    "subject" => "all"
                ]
            ]
        ]);

        $roleUser = Role::create([
            'name' => 'User',
            'permissions' => [
                [
                    "parent" => "all",
                    "action" => "read",
                    "subject" => "all"
                ]
            ]
        ]);

        $createSuperAdmin = User::create([
            'username' => 'superadmin',
            'email' => 'superadmin@gmail.com',
            'password' => bcrypt('superadmin'),
            'role_id' => $roleSuperAdmin->id,
            'is_active' => 1
        ]);

        $createAdmin = User::create([
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin'),
            'role_id' => $roleAdmin->id,
            'is_active' => 1
        ]);

        $createUser = User::create([
            'username' => 'user',
            'email' => 'user@gmail.com',
            'password' => bcrypt('user'),
            'role_id' => $roleUser->id,
            'is_active' => 1
        ]);

        Brand::create([
            'brand_name' => 'Nestle',
            'user_id' => $createSuperAdmin->id
        ]);

        Brand::create([
            'brand_name' => 'Ultramilk',
            'user_id' => $createAdmin->id
        ]);

        Brand::create([
            'brand_name' => 'Indomie',
            'user_id' => $createUser->id
        ]);

        Category::create([
            'category_name' => 'Minuman',
            'user_id' => $createSuperAdmin->id
        ]);

        Category::create([
            'category_name' => 'Makanan',
            'user_id' => $createAdmin->id
        ]);

        SubCategory::create([
            'sub_category_name' => 'Susu',
            'category_id' => 1,
            'user_id' => $createSuperAdmin->id
        ]);

        SubCategory::create([
            'sub_category_name' => 'Mie Instan',
            'category_id' => 2,
            'user_id' => $createAdmin->id
        ]);

        Unit::create([
            'unit_name' => 'Botol',
            'user_id' => $createSuperAdmin->id
        ]);

        Unit::create([
            'unit_name' => 'Pcs',
            'user_id' => $createAdmin->id
        ]);

        Unit::create([
            'unit_name' => 'Pack',
            'user_id' => $createUser->id
        ]);

        Metric::create([
            'metric_name' => 'Liter',
            'user_id' => $createSuperAdmin->id
        ]);

        Metric::create([
            'metric_name' => 'Gram',
            'user_id' => $createAdmin->id
        ]);

        Metric::create([
            'metric_name' => 'Mililiter',
            'user_id' => $createUser->id
        ]);

        Inventory::create([
            'inventory_name' => 'Kitchen Pradita',
            'user_id' => $createSuperAdmin->id
        ]);

        Inventory::create([
            'inventory_name' => 'Outlet',
            'user_id' => $createAdmin->id
        ]);

        Storage::create([
            'storage_name' => 'Freezer',
            'inventory_id' => 1,
            'user_id' => $createSuperAdmin->id
        ]);

        Storage::create([
            'storage_name' => 'Chiller',
            'inventory_id' => 2,
            'user_id' => $createAdmin->id
        ]);

        Supplier::create([
            'supplier_name' => 'PT. Nestle Indonesia',
            'user_id' => $createSuperAdmin->id
        ]);

        Supplier::create([
            'supplier_name' => 'PT. Indofood Indonesia',
            'user_id' => $createAdmin->id
        ]);

        // $table->string('product_code');
        // $table->string('product_name');
        // $table->foreignId('brand_id')->nullable(); //udah
        // $table->foreignId('sub_category_id')->nullable(); //udah(nicxon)
        // $table->double('min_stock');
        // $table->double('stock'); //in units
        // $table->boolean('automatic_use')->default(0);
        // $table->double('purchase_price')->default(0);
        // $table->double('selling_price')->default(0);
        // $table->foreignId('unit_id')->nullable(); //udah
        // $table->double('measurement')->default(0); //in metric
        // $table->foreignId('metric_id')->nullable();
        // $table->text('image')->nullable();
        // $table->foreignId('storage_id')->nullable(); //udah
        // $table->foreignId('supplier_id')->nullable();
        // $table->enum('product_type', ['raw', 'finished']);
        // $table->boolean('is_active')->default(true);
        // $table->foreignId('user_id');

        Product::create([
            'product_code' => 'PRD-2024-0001',
            'product_name' => 'Bear Brand',
            'brand_id' => 1,
            'sub_category_id' => 1,
            'min_stock' => 100,
            'stock' => 100,
            'automatic_use' => 0,
            'purchase_price' => 10000,
            'selling_price' => 0,
            'unit_id' => 1,
            'measurement' => 1000,
            'metric_id' => 1,
            'image' => null,
            'storage_id' => 1,
            'supplier_id' => 1,
            'product_type' => 'raw',
            'user_id' => $createSuperAdmin->id
        ]);

        Product::create([
            'product_code' => 'PRD-2024-0002',
            'product_name' => 'Bawang Putih',
            'brand_id' => 2,
            'sub_category_id' => 2,
            'min_stock' => 50,
            'stock' => 50,
            'automatic_use' => 0,
            'purchase_price' => 5000,
            'selling_price' => 0,
            'unit_id' => 2,
            'measurement' => 10,
            'metric_id' => 2,
            'image' => null,
            'storage_id' => 2,
            'supplier_id' => 2,
            'product_type' => 'raw',
            'user_id' => $createAdmin->id
        ]);

        Product::create([
            'product_code' => 'PRD-2024-0003',
            'product_name' => 'Nasi Goreng',
            'brand_id' => 3,
            'sub_category_id' => 2,
            'min_stock' => 10,
            'stock' => 10,
            'automatic_use' => 0,
            'purchase_price' => 2000,
            'selling_price' => 0,
            'unit_id' => 3,
            'measurement' => 1,
            'metric_id' => 3,
            'image' => null,
            'storage_id' => 2,
            'supplier_id' => 2,
            'product_type' => 'finished',
            'user_id' => $createUser->id
        ]);

        ProductInfo::create([
            'product_id' => 1,
            'total_stock' => 100,
            'total_stock_out' => 0,
            'inventory_id' => 1,
            'user_id' => 1,
        ]);

        ProductInfo::create([
            'product_id' => 2,
            'total_stock' => 50,
            'total_stock_out' => 0,
            'inventory_id' => 1,
            'user_id' => 2,
        ]);

        ProductInfo::create([
            'product_id' => 3,
            'total_stock' => 10,
            'total_stock_out' => 0,
            'inventory_id' => 2,
            'user_id' => 3,
        ]);

        ProductHistory::create([
            'product_id' => 1,
            'date' => now(),
            'quantity' => 100,
            'purchase_price' => 10000,
            'selling_price' => 0,
            'total' => 10000,
            'discount_type' => 'percentage',
            'discount_amount' => 0,
            'discount_percentage' => 0,
            'grandtotal' => 1000000,
            'remaining_stock' => 100,
            'reference_number' => 'PRD-2024-0001',
            'category' => 'initial-stock',
            'type' => 'IN',
            'inventory_id' => 1,
        ]);

        ProductHistory::create([
            'product_id' => 2,
            'date' => now(),
            'quantity' => 50,
            'purchase_price' => 5000,
            'selling_price' => 0,
            'total' => 5000,
            'discount_type' => 'percentage',
            'discount_amount' => 0,
            'discount_percentage' => 0,
            'grandtotal' => 250000,
            'remaining_stock' => 50,
            'reference_number' => 'PRD-2024-0002',
            'category' => 'initial-stock',
            'type' => 'IN',
            'inventory_id' => 1,
        ]);

        ProductHistory::create([
            'product_id' => 3,
            'date' => now(),
            'quantity' => 10,
            'purchase_price' => 2000,
            'selling_price' => 0,
            'total' => 2000,
            'discount_type' => 'percentage',
            'discount_amount' => 0,
            'discount_percentage' => 0,
            'grandtotal' => 20000,
            'remaining_stock' => 10,
            'reference_number' => 'PRD-2024-0003',
            'category' => 'initial-stock',
            'type' => 'IN',
            'inventory_id' => 2,
        ]);

        Description::create([
            'description_name' => 'RECEIVING PURCHASE',
            'user_id' => 1
        ]);

        Description::create([
            'description_name' => 'MK PRODUCTION - PIP',
            'user_id' => 1
        ]);

        Description::create([
            'description_name' => 'PAR STOCK ADJUSTMENT',
            'user_id' => 1
        ]);

        Description::create([
            'description_name' => 'EMERGENCY PURCHASE',
            'user_id' => 1
        ]);

        Description::create([
            'description_name' => 'MK RETURNED',
            'user_id' => 1
        ]);

        Description::create([
            'description_name' => 'MK PRODUCTION - CATERING',
            'user_id' => 1
        ]);
    }
}
