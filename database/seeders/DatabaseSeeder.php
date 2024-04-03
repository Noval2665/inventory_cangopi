<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Metric;
use App\Models\Role;
use App\Models\Storage;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

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
            'name' => 'Super Admin',
            'permissions' => [
                [
                    "action" => "manage",
                    "subject" => "all"
                ]
            ]
        ]);

        $roleAdmin = Role::create([
            'name' => 'Admin',
            'permissions' => [
                [
                    "action" => "manage",
                    "subject" => "all"
                ]
            ]
        ]);

        $roleUser = Role::create([
            'name' => 'User',
            'permissions' => [
                [
                    "action" => "read",
                    "subject" => "all"
                ]
            ]
        ]);

        $createSuperAdmin = User::create([
            'username' => 'superadmin',
            'email' => 'superadmin@gmail.com',
            'password' => bcrypt('superadmin'),
            'role_id' => $roleSuperAdmin->id
        ]);

        $createAdmin = User::create([
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin'),
            'role_id' => $roleAdmin->id
        ]);

        $createUser = User::create([
            'username' => 'user',
            'email' => 'user@gmail.com',
            'password' => bcrypt('user'),
            'role_id' => $roleUser->id
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
    }
}
