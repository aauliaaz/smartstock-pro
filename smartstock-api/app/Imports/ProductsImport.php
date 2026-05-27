<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Row;

class ProductsImport implements OnEachRow, WithHeadingRow, WithChunkReading
{
    public int $success = 0;
    public int $failed = 0;
    public array $errors = [];

    public function onRow(Row $row): void
    {
        $r = $row->toArray();
        $lineNum = $row->getIndex();

        try {
            $sku = trim((string) ($r['sku'] ?? $r['kode_barang'] ?? ''));
            $name = trim((string) ($r['name'] ?? $r['nama_barang'] ?? ''));
            $categoryName = trim((string) ($r['category'] ?? $r['kategori'] ?? 'Umum'));
            $unit = strtolower(trim((string) ($r['unit'] ?? $r['satuan'] ?? 'pcs')));
            $minStock = (int) ($r['min_stock'] ?? $r['stok_minimum'] ?? 0);
            $priceBuy = (float) ($r['price_buy'] ?? $r['harga_beli'] ?? 0);
            $priceSell = (float) ($r['price_sell'] ?? $r['harga_jual'] ?? 0);

            if (empty($sku)) throw new \Exception('SKU kosong');
            if (empty($name)) throw new \Exception('Nama produk kosong');
            if (Product::where('sku', $sku)->exists()) throw new \Exception("SKU {$sku} sudah ada");
            if ($priceSell < $priceBuy) throw new \Exception('Harga jual harus >= harga beli');

            $category = Category::firstOrCreate(['name' => $categoryName]);

            $product = Product::create([
                'sku' => strtoupper($sku),
                'name' => $name,
                'category_id' => $category->id,
                'unit' => $unit,
                'min_stock' => $minStock,
                'price_buy' => $priceBuy,
                'price_sell' => $priceSell,
                'is_active' => true,
            ]);

            // Init stok di semua gudang
            foreach (Warehouse::where('is_active', true)->get() as $wh) {
                ProductWarehouseStock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $wh->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            $this->success++;
        } catch (\Throwable $e) {
            $this->failed++;
            $this->errors[] = [
                'line' => $lineNum,
                'sku' => $sku ?? '',
                'name' => $name ?? '',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
