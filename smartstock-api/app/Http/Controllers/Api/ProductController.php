<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductWarehouseStock;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['category:id,name', 'primaryImage:id,product_id,path']);

        // Search (Tahap 9.1 - simplified)
        if ($search = $request->input('search')) {
            $q = strtolower(trim($search));
            $query->where(function ($w) use ($q) {
                $w->whereRaw('LOWER(sku) LIKE ?', ["%{$q}%"])
                  ->orWhereRaw('LOWER(name) LIKE ?', ["%{$q}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$q}%"]);
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        if ($request->boolean('low_stock')) {
            $query->whereHas('stocks', fn ($q) => $q->whereColumn('quantity', '<=', 'products.min_stock'));
        }

        $sort = $request->input('sort', '-created_at');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortField = ltrim($sort, '-');
        if (in_array($sortField, ['name', 'sku', 'price_sell', 'created_at', 'updated_at'])) {
            $query->orderBy($sortField, $direction);
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $products = $query->paginate($perPage);

        $products->getCollection()->transform(function (Product $p) {
            $totalStock = (int) $p->stocks()->sum('quantity');
            return [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'description' => $p->description,
                'category' => $p->category ? ['id' => $p->category->id, 'name' => $p->category->name] : null,
                'unit' => $p->unit,
                'min_stock' => $p->min_stock,
                'total_stock' => $totalStock,
                'is_low_stock' => $totalStock <= $p->min_stock,
                'price_buy' => (float) $p->price_buy,
                'price_sell' => (float) $p->price_sell,
                'is_active' => $p->is_active,
                'primary_image' => $p->primaryImage?->path,
                'created_at' => $p->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with(['category', 'images', 'stocks.warehouse'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category,
                'unit' => $product->unit,
                'min_stock' => $product->min_stock,
                'price_buy' => (float) $product->price_buy,
                'price_sell' => (float) $product->price_sell,
                'is_active' => $product->is_active,
                'images' => $product->images,
                'stocks' => $product->stocks->map(fn ($s) => [
                    'warehouse_id' => $s->warehouse_id,
                    'warehouse_name' => $s->warehouse?->name,
                    'warehouse_code' => $s->warehouse?->code,
                    'quantity' => $s->quantity,
                    'reserved_quantity' => $s->reserved_quantity,
                    'available' => $s->quantity - $s->reserved_quantity,
                ]),
                'total_stock' => (int) $product->stocks->sum('quantity'),
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'unit' => ['required', 'string', 'max:20'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'price_buy' => ['required', 'numeric', 'min:0'],
            'price_sell' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $product = DB::transaction(function () use ($data) {
            $product = Product::create($data);

            // Init stok 0 di semua gudang aktif
            $warehouses = Warehouse::where('is_active', true)->get();
            foreach ($warehouses as $wh) {
                ProductWarehouseStock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $wh->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            AuditLog::record('CREATE', $product, null, $data);
            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan.',
            'data' => $product->load('category'),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'sku' => ['sometimes', 'string', 'max:50', "unique:products,sku,{$id}"],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'unit' => ['sometimes', 'string', 'max:20'],
            'min_stock' => ['sometimes', 'integer', 'min:0'],
            'price_buy' => ['sometimes', 'numeric', 'min:0'],
            'price_sell' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $old = $product->toArray();
        $product->update($data);
        AuditLog::record('UPDATE', $product, $old, $data);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diupdate.',
            'data' => $product->fresh()->load('category'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        AuditLog::record('DELETE', $product, $product->toArray());
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = strtolower(trim($request->input('q', '')));
        if (strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $products = Product::where('is_active', true)
            ->where(function ($w) use ($q) {
                $w->whereRaw('LOWER(sku) LIKE ?', ["%{$q}%"])
                  ->orWhereRaw('LOWER(name) LIKE ?', ["%{$q}%"]);
            })
            ->limit(20)
            ->get(['id', 'sku', 'name', 'unit', 'price_sell']);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function uploadImage(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_primary' => ['boolean'],
        ]);

        $path = $request->file('image')->store('products', 'public');

        if ($request->boolean('is_primary')) {
            $product->images()->update(['is_primary' => false]);
        }

        $img = ProductImage::create([
            'product_id' => $product->id,
            'path' => $path,
            'is_primary' => $request->boolean('is_primary', $product->images()->count() === 0),
        ]);

        return response()->json([
            'success' => true,
            'data' => $img,
        ], 201);
    }

    public function deleteImage(int $id, int $imageId): JsonResponse
    {
        $image = ProductImage::where('product_id', $id)->findOrFail($imageId);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json(['success' => true, 'message' => 'Gambar dihapus.']);
    }
}
