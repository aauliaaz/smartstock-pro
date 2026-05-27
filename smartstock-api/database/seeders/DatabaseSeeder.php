<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============== 1. Roles ==============
        $roles = [
            ['code' => 'ADM', 'name' => 'Administrator', 'description' => 'Full access ke semua fitur'],
            ['code' => 'MGR', 'name' => 'Manajer Gudang', 'description' => 'Approve transfer, kelola data master'],
            ['code' => 'STF', 'name' => 'Staf Gudang', 'description' => 'Input transaksi & request transfer'],
            ['code' => 'VWR', 'name' => 'Viewer', 'description' => 'Read-only akses'],
        ];
        foreach ($roles as $r) {
            Role::firstOrCreate(['code' => $r['code']], $r);
        }
        $admRole = Role::where('code', 'ADM')->first();
        $mgrRole = Role::where('code', 'MGR')->first();
        $stfRole = Role::where('code', 'STF')->first();
        $vwrRole = Role::where('code', 'VWR')->first();

        // ============== 2. Warehouses ==============
        $warehouses = [
            ['code' => 'JKT', 'name' => 'Gudang Jakarta', 'city' => 'Jakarta', 'address' => 'Jl. Sudirman No. 1, Jakarta Pusat', 'latitude' => -6.2088, 'longitude' => 106.8456, 'capacity' => 10000],
            ['code' => 'SBY', 'name' => 'Gudang Surabaya', 'city' => 'Surabaya', 'address' => 'Jl. Pemuda No. 27, Surabaya', 'latitude' => -7.2575, 'longitude' => 112.7521, 'capacity' => 8000],
            ['code' => 'BDG', 'name' => 'Gudang Bandung', 'city' => 'Bandung', 'address' => 'Jl. Asia Afrika No. 15, Bandung', 'latitude' => -6.9175, 'longitude' => 107.6191, 'capacity' => 6000],
            ['code' => 'MDN', 'name' => 'Gudang Medan', 'city' => 'Medan', 'address' => 'Jl. Gatot Subroto No. 50, Medan', 'latitude' => 3.5952, 'longitude' => 98.6722, 'capacity' => 5000],
            ['code' => 'MKS', 'name' => 'Gudang Makassar', 'city' => 'Makassar', 'address' => 'Jl. AP Pettarani No. 88, Makassar', 'latitude' => -5.1477, 'longitude' => 119.4327, 'capacity' => 4000],
        ];
        foreach ($warehouses as $w) {
            Warehouse::firstOrCreate(['code' => $w['code']], array_merge($w, ['is_active' => true]));
        }

        // ============== 3. Users ==============
        $whJkt = Warehouse::where('code', 'JKT')->first();
        $whSby = Warehouse::where('code', 'SBY')->first();
        $whBdg = Warehouse::where('code', 'BDG')->first();
        $whMdn = Warehouse::where('code', 'MDN')->first();
        $whMks = Warehouse::where('code', 'MKS')->first();

        $users = [
            ['name' => 'Administrator', 'email' => 'admin@smartstock.id', 'password' => 'Admin@123', 'role_id' => $admRole->id, 'warehouse_id' => null],
            ['name' => 'Budi Manajer', 'email' => 'manajer@smartstock.id', 'password' => 'Manajer@123', 'role_id' => $mgrRole->id, 'warehouse_id' => null],
            ['name' => 'Siti Staf Jakarta', 'email' => 'staf.jkt@smartstock.id', 'password' => 'Staf@123', 'role_id' => $stfRole->id, 'warehouse_id' => $whJkt->id],
            ['name' => 'Andi Staf Surabaya', 'email' => 'staf.sby@smartstock.id', 'password' => 'Staf@123', 'role_id' => $stfRole->id, 'warehouse_id' => $whSby->id],
            ['name' => 'Rina Staf Bandung', 'email' => 'staf.bdg@smartstock.id', 'password' => 'Staf@123', 'role_id' => $stfRole->id, 'warehouse_id' => $whBdg->id],
            ['name' => 'Joko Staf Medan', 'email' => 'staf.mdn@smartstock.id', 'password' => 'Staf@123', 'role_id' => $stfRole->id, 'warehouse_id' => $whMdn->id],
            ['name' => 'Dewi Staf Makassar', 'email' => 'staf.mks@smartstock.id', 'password' => 'Staf@123', 'role_id' => $stfRole->id, 'warehouse_id' => $whMks->id],
            ['name' => 'Sales Viewer', 'email' => 'viewer@smartstock.id', 'password' => 'Viewer@123', 'role_id' => $vwrRole->id, 'warehouse_id' => null],
        ];
        foreach ($users as $u) {
            User::firstOrCreate(['email' => $u['email']], array_merge($u, ['is_active' => true]));
        }

        // ============== 4. Categories ==============
        $categories = ['Laptop', 'Smartphone', 'Tablet', 'TV', 'Audio', 'Aksesoris', 'Networking', 'Storage'];
        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat]);
        }

        // ============== 5. Suppliers ==============
        $suppliers = [
            ['code' => 'SUP-001', 'name' => 'PT Asus Distributor Indonesia', 'phone' => '021-5550001', 'email' => 'sales@asus.id', 'pic_name' => 'Ahmad'],
            ['code' => 'SUP-002', 'name' => 'PT Samsung Electronics', 'phone' => '021-5550002', 'email' => 'b2b@samsung.com', 'pic_name' => 'Lisa'],
            ['code' => 'SUP-003', 'name' => 'CV Sumber Elektronik', 'phone' => '021-5550003', 'email' => 'cs@sumber-elc.id', 'pic_name' => 'Heri'],
            ['code' => 'SUP-004', 'name' => 'PT Xiaomi Indonesia', 'phone' => '021-5550004', 'email' => 'partner@xiaomi.id', 'pic_name' => 'Tina'],
        ];
        foreach ($suppliers as $s) {
            Supplier::firstOrCreate(['code' => $s['code']], array_merge($s, ['is_active' => true]));
        }

        // ============== 6. Products ==============
        $catLaptop = Category::where('name', 'Laptop')->first();
        $catPhone = Category::where('name', 'Smartphone')->first();
        $catTab = Category::where('name', 'Tablet')->first();
        $catTV = Category::where('name', 'TV')->first();
        $catAudio = Category::where('name', 'Audio')->first();
        $catAcc = Category::where('name', 'Aksesoris')->first();

        $products = [
            ['sku' => 'LP-ASUS-001', 'name' => 'Laptop Asus VivoBook 14', 'category_id' => $catLaptop->id, 'unit' => 'unit', 'min_stock' => 5, 'price_buy' => 6500000, 'price_sell' => 7999000],
            ['sku' => 'LP-LENOVO-001', 'name' => 'Laptop Lenovo IdeaPad 3', 'category_id' => $catLaptop->id, 'unit' => 'unit', 'min_stock' => 5, 'price_buy' => 5800000, 'price_sell' => 7299000],
            ['sku' => 'LP-HP-001', 'name' => 'Laptop HP Pavilion 15', 'category_id' => $catLaptop->id, 'unit' => 'unit', 'min_stock' => 3, 'price_buy' => 9500000, 'price_sell' => 11999000],
            ['sku' => 'SP-SAMSUNG-A54', 'name' => 'Samsung Galaxy A54 5G', 'category_id' => $catPhone->id, 'unit' => 'unit', 'min_stock' => 10, 'price_buy' => 4200000, 'price_sell' => 5499000],
            ['sku' => 'SP-IPHONE-15', 'name' => 'iPhone 15 128GB', 'category_id' => $catPhone->id, 'unit' => 'unit', 'min_stock' => 3, 'price_buy' => 14500000, 'price_sell' => 17999000],
            ['sku' => 'SP-XIAOMI-13', 'name' => 'Xiaomi Redmi Note 13', 'category_id' => $catPhone->id, 'unit' => 'unit', 'min_stock' => 10, 'price_buy' => 2800000, 'price_sell' => 3499000],
            ['sku' => 'TB-IPAD-AIR', 'name' => 'iPad Air 11" M2', 'category_id' => $catTab->id, 'unit' => 'unit', 'min_stock' => 3, 'price_buy' => 11500000, 'price_sell' => 13999000],
            ['sku' => 'TV-SAMSUNG-43', 'name' => 'Samsung Smart TV 43"', 'category_id' => $catTV->id, 'unit' => 'unit', 'min_stock' => 2, 'price_buy' => 4500000, 'price_sell' => 5799000],
            ['sku' => 'TV-LG-55', 'name' => 'LG OLED TV 55"', 'category_id' => $catTV->id, 'unit' => 'unit', 'min_stock' => 1, 'price_buy' => 14000000, 'price_sell' => 17999000],
            ['sku' => 'AU-SONY-WH1000', 'name' => 'Sony WH-1000XM5 Headphone', 'category_id' => $catAudio->id, 'unit' => 'unit', 'min_stock' => 5, 'price_buy' => 3800000, 'price_sell' => 4799000],
            ['sku' => 'AU-JBL-CHARGE5', 'name' => 'JBL Charge 5 Speaker', 'category_id' => $catAudio->id, 'unit' => 'unit', 'min_stock' => 5, 'price_buy' => 1800000, 'price_sell' => 2299000],
            ['sku' => 'AC-MOUSE-LOGI', 'name' => 'Mouse Logitech M170', 'category_id' => $catAcc->id, 'unit' => 'pcs', 'min_stock' => 50, 'price_buy' => 95000, 'price_sell' => 145000],
            ['sku' => 'AC-KEYBOARD-LOGI', 'name' => 'Keyboard Logitech K120', 'category_id' => $catAcc->id, 'unit' => 'pcs', 'min_stock' => 30, 'price_buy' => 145000, 'price_sell' => 199000],
            ['sku' => 'AC-CHARGER-USB', 'name' => 'Charger USB-C 65W', 'category_id' => $catAcc->id, 'unit' => 'pcs', 'min_stock' => 40, 'price_buy' => 180000, 'price_sell' => 249000],
            ['sku' => 'AC-CABLE-HDMI', 'name' => 'Kabel HDMI 2.0 1.5m', 'category_id' => $catAcc->id, 'unit' => 'pcs', 'min_stock' => 100, 'price_buy' => 35000, 'price_sell' => 65000],
        ];

        foreach ($products as $p) {
            $product = Product::firstOrCreate(['sku' => $p['sku']], array_merge($p, ['is_active' => true]));

            // Init stok di tiap gudang
            foreach (Warehouse::all() as $wh) {
                $qty = rand(0, 100);
                // Sengaja buat beberapa produk low stock untuk demo alert
                if (in_array($p['sku'], ['LP-HP-001', 'TV-LG-55', 'SP-IPHONE-15']) && $wh->code === 'JKT') {
                    $qty = rand(0, $p['min_stock']);
                }
                ProductWarehouseStock::firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $wh->id],
                    ['quantity' => $qty, 'reserved_quantity' => 0]
                );
            }
        }

        // ============== 7. Stock Movements (Histori 30 hari) ==============
        $adminUser = User::where('email', 'admin@smartstock.id')->first();
        $supplier = Supplier::first();
        $allProducts = Product::all();
        $allWarehouses = Warehouse::all();

        for ($d = 30; $d >= 0; $d--) {
            $date = now()->subDays($d);
            // IN
            $inCount = rand(2, 5);
            for ($i = 0; $i < $inCount; $i++) {
                $product = $allProducts->random();
                $warehouse = $allWarehouses->random();
                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'supplier_id' => $supplier->id,
                    'user_id' => $adminUser->id,
                    'type' => 'IN',
                    'quantity' => rand(5, 30),
                    'unit_price' => $product->price_buy,
                    'movement_date' => $date,
                    'note' => 'Restock awal',
                ]);
            }
            // OUT
            $outCount = rand(3, 7);
            for ($i = 0; $i < $outCount; $i++) {
                $product = $allProducts->random();
                $warehouse = $allWarehouses->random();
                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $adminUser->id,
                    'type' => 'OUT',
                    'quantity' => rand(1, 10),
                    'unit_price' => $product->price_sell,
                    'movement_date' => $date,
                    'note' => 'Penjualan ke customer',
                ]);
            }
        }

        echo "\n=== SEED COMPLETE ===\n";
        echo "Admin:           admin@smartstock.id / Admin@123\n";
        echo "Manager:         manajer@smartstock.id / Manajer@123\n";
        echo "Staff Jakarta:   staf.jkt@smartstock.id / Staf@123\n";
        echo "Viewer:          viewer@smartstock.id / Viewer@123\n";
    }
}
