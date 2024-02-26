<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Role;
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
    }
}
