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
use App\Models\AuditLog;

class InventoryFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $product;
    protected $jakarta;
    protected $surabaya;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@smartstock.pro',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id
        ]);

        $category = Category::create(['name' => 'Smartphone', 'slug' => 'smartphone']);
        $this->product = Product::create([
            'name' => 'iPhone 15',
            'sku' => 'IPH15',
            'category_id' => $category->id,
            'unit_price' => 15000000,
            'min_threshold' => 10
        ]);

        $this->jakarta = Warehouse::create(['name' => 'Jakarta', 'location' => 'JKT', 'city' => 'Jakarta']);
        $this->surabaya = Warehouse::create(['name' => 'Surabaya', 'location' => 'SBY', 'city' => 'Surabaya']);
    }

    public function test_full_inventory_lifecycle(): void
    {
        // 1. Stock In
        $response = $this->actingAs($this->admin)->postJson('/api/stock-movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->jakarta->id,
            'quantity' => 100,
            'type' => 'IN',
            'reference' => 'PO-001'
        ]);
        $response->assertStatus(201);
        $this->assertEquals(100, $this->product->getTotalStock());

        // 2. Verify Audit Log for Stock In
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'STOCK_IN',
            'model_type' => 'StockMovement'
        ]);

        // 3. Stock Out (Partial)
        $response = $this->actingAs($this->admin)->postJson('/api/stock-movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->jakarta->id,
            'quantity' => 20,
            'type' => 'OUT',
            'reference' => 'SO-001'
        ]);
        $response->assertStatus(201);
        $this->assertEquals(80, $this->product->getTotalStock());

        // 4. Warehouse Transfer Request
        $response = $this->actingAs($this->admin)->postJson('/api/transfers', [
            'from_warehouse_id' => $this->jakarta->id,
            'to_warehouse_id' => $this->surabaya->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 30]
            ],
            'notes' => 'Transfer to Surabaya'
        ]);
        $response->assertStatus(201);
        $transferId = $response->json('id');

        // 5. Approve Transfer
        $response = $this->actingAs($this->admin)->patchJson("/api/transfers/{$transferId}/approve");
        $response->assertStatus(200);

        // 6. Verify Final Stocks
        $this->assertEquals(50, $this->product->getStockAtWarehouse($this->jakarta->id));
        $this->assertEquals(30, $this->product->getStockAtWarehouse($this->surabaya->id));
        $this->assertEquals(80, $this->product->getTotalStock());

        // 7. Verify FIFO Valuation (Simplified check)
        $value = $this->product->getInventoryValue();
        $this->assertEquals(80 * 15000000, $value);

        // 8. Check Dashboard Data
        $response = $this->actingAs($this->admin)->getJson('/api/dashboard');
        $response->assertStatus(200)
                 ->assertJsonPath('summary.total_stock', 80);
    }
}
