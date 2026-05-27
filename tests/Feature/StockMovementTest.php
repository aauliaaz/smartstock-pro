<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Role;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_record_stock_in(): void
    {
        // Seed basic data
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id
        ]);
        
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech']);
        $product = Product::create([
            'name' => 'Laptop',
            'sku' => 'LP-001',
            'category_id' => $category->id,
            'unit_price' => 1000,
            'min_threshold' => 5
        ]);
        
        $warehouse = Warehouse::create([
            'name' => 'Jakarta',
            'location' => 'JKT',
            'city' => 'Jakarta'
        ]);

        $response = $this->actingAs($user)->postJson('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'type' => 'IN',
            'notes' => 'Test Stock In'
        ]);

        $response->assertStatus(201);
        $this->assertEquals(10, $product->getTotalStock());
    }

    public function test_cannot_record_stock_out_if_insufficient(): void
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id
        ]);
        
        $category = Category::create(['name' => 'Tech', 'slug' => 'tech']);
        $product = Product::create([
            'name' => 'Laptop',
            'sku' => 'LP-001',
            'category_id' => $category->id,
            'unit_price' => 1000,
            'min_threshold' => 5
        ]);
        
        $warehouse = Warehouse::create(['name' => 'Jakarta', 'location' => 'JKT', 'city' => 'Jakarta']);

        $response = $this->actingAs($user)->postJson('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'type' => 'OUT'
        ]);

        $response->assertStatus(422);
    }
}
