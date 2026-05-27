<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Role;
use App\Models\StockMovement;

class WarehouseTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_transfer_stock_between_warehouses(): void
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
        
        $jakarta = Warehouse::create(['name' => 'Jakarta', 'location' => 'JKT', 'city' => 'Jakarta']);
        $surabaya = Warehouse::create(['name' => 'Surabaya', 'location' => 'SBY', 'city' => 'Surabaya']);

        // Add initial stock to Jakarta
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $jakarta->id,
            'user_id' => $user->id,
            'quantity' => 100,
            'type' => 'IN'
        ]);

        // Create transfer request
        $response = $this->actingAs($user)->postJson('/api/transfers', [
            'from_warehouse_id' => $jakarta->id,
            'to_warehouse_id' => $surabaya->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 30]
            ]
        ]);

        $response->assertStatus(201);
        $transferId = $response->json('id');

        // Approve transfer
        $approveResponse = $this->actingAs($user)->patchJson("/api/transfers/{$transferId}/approve");
        $approveResponse->assertStatus(200);

        // Verify stock levels
        $this->assertEquals(70, $product->getStockAtWarehouse($jakarta->id));
        $this->assertEquals(30, $product->getStockAtWarehouse($surabaya->id));
    }
}
