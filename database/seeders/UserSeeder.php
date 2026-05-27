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

        User::updateOrCreate(['email' => 'admin@smartstock.id'], [
            'name' => 'Administrator',
            'password' => Hash::make('Admin@123'),
            'role_id' => $adminRole->id,
        ])->notifications()->firstOrCreate([
            'title' => 'Welcome to SmartStock Pro',
        ], [
            'title' => 'Welcome to SmartStock Pro',
            'message' => 'System successfully initialized. You have full access.',
            'type' => 'INFO'
        ]);

        User::updateOrCreate(['email' => 'manager@smartstock.id'], [
            'name' => 'Manager Surabaya',
            'password' => Hash::make('Manager@123'),
            'role_id' => $managerRole->id,
        ])->notifications()->firstOrCreate([
            'title' => 'Stock Alert',
        ], [
            'title' => 'Stock Alert',
            'message' => 'iPhone 15 Pro stock is below threshold in Surabaya.',
            'type' => 'WARNING'
        ]);

        User::updateOrCreate(['email' => 'staff@smartstock.id'], [
            'name' => 'Staff Jakarta',
            'password' => Hash::make('Staff@123'),
            'role_id' => $staffRole->id,
        ]);

        User::updateOrCreate(['email' => 'viewer@smartstock.id'], [
            'name' => 'Viewer Only',
            'password' => Hash::make('Viewer@123'),
            'role_id' => $viewerRole->id,
        ]);
    }
}
