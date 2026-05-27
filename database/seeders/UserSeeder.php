<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $managerRole = Role::where('slug', 'manager')->first();
        $staffRole = Role::where('slug', 'staff')->first();
        $viewerRole = Role::where('slug', 'viewer')->first();

        User::create([
            'name' => 'Administrator',
            'email' => 'admin@smartstock.pro',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ])->notifications()->create([
            'title' => 'Welcome to SmartStock Pro',
            'message' => 'System successfully initialized. You have full access.',
            'type' => 'INFO'
        ]);

        User::create([
            'name' => 'Manager Surabaya',
            'email' => 'manager.sby@smartstock.pro',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
        ])->notifications()->create([
            'title' => 'Stock Alert',
            'message' => 'iPhone 15 Pro stock is below threshold in Surabaya.',
            'type' => 'WARNING'
        ]);

        User::create([
            'name' => 'Staff Jakarta',
            'email' => 'staff.jkt@smartstock.pro',
            'password' => Hash::make('password'),
            'role_id' => $staffRole->id,
        ]);

        User::create([
            'name' => 'Viewer Only',
            'email' => 'viewer@smartstock.pro',
            'password' => Hash::make('password'),
            'role_id' => $viewerRole->id,
        ]);
    }
}
