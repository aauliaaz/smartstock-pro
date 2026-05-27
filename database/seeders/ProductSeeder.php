<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Product;
use App\Models\Category;
use App\Models\Warehouse;
use App\Models\StockMovement;
use App\Models\User;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        $warehouses = Warehouse::all();
        $admin = User::first();

        $products = [
            ['name' => 'iPhone 15 Pro', 'sku' => 'IPH15P', 'category_id' => $categories->where('slug', 'smartphone')->first()->id, 'unit_price' => 15000000, 'min_threshold' => 5],
            ['name' => 'MacBook Air M2', 'sku' => 'MBA-M2', 'category_id' => $categories->where('slug', 'laptop')->first()->id, 'unit_price' => 18000000, 'min_threshold' => 3],
            ['name' => 'AirPods Pro 2', 'sku' => 'APP2', 'category_id' => $categories->where('slug', 'accessories')->first()->id, 'unit_price' => 3500000, 'min_threshold' => 10],
            ['name' => 'Asus ROG Zephyrus', 'sku' => 'ROG-Z', 'category_id' => $categories->where('slug', 'laptop')->first()->id, 'unit_price' => 25000000, 'min_threshold' => 2],
            ['name' => 'Logitech MX Master 3S', 'sku' => 'MXM3S', 'category_id' => $categories->where('slug', 'accessories')->first()->id, 'unit_price' => 1500000, 'min_threshold' => 8],
        ];

        foreach ($products as $productData) {
            $product = Product::create($productData);

            // Add initial stock for each warehouse
            foreach ($warehouses as $warehouse) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $admin->id,
                    'quantity' => rand(10, 50),
                    'type' => 'IN',
                    'notes' => 'Initial Stock Seeding'
                ]);
            }
        }
    }
}
