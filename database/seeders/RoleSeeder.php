<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Full system access'],
            ['name' => 'Manajer Gudang', 'slug' => 'manager', 'description' => 'Manage stock and transfers'],
            ['name' => 'Staf Gudang', 'slug' => 'staff', 'description' => 'Input stock movements'],
            ['name' => 'Viewer', 'slug' => 'viewer', 'description' => 'Read-only access'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
