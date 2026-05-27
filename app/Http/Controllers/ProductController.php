<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use App\Models\AuditLog;
use App\Jobs\BatchImportProducts;

class ProductController extends Controller
{
    public function import(Request $request)
    {
        $data = [
            ['name' => 'Imported Phone 1', 'sku' => 'IMP-001', 'category_id' => 1, 'unit_price' => 5000000],
            ['name' => 'Imported Laptop 2', 'sku' => 'IMP-002', 'category_id' => 2, 'unit_price' => 12000000],
        ];

        BatchImportProducts::dispatch($data);

        AuditLog::create([
            'user_id' => $request->user()->id ?? 1,
            'action' => 'BATCH_IMPORT_DISPATCHED',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json(['message' => 'Import job has been dispatched. Products will appear shortly.']);
    }

    public function index(Request $request)
    {
        $query = Product::with(['category', 'images']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Requirement 3e: Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate(15);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku',
            'category_id' => 'required|exists:categories,id',
            'unit_price' => 'required|numeric|min:0',
            'min_threshold' => 'integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $product = Product::create($validated);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => true
            ]);
        }

        AuditLog::create([
            'user_id' => $request->user()->id ?? 1,
            'action' => 'CREATE_PRODUCT',
            'model_type' => 'Product',
            'model_id' => $product->id,
            'new_values' => $product->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json($product->load('images'), 201);
    }

    public function show(Product $product)
    {
        return $product->load(['category', 'images', 'stockMovements.warehouse']);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'category_id' => 'exists:categories,id',
            'unit_price' => 'numeric|min:0',
            'min_threshold' => 'integer|min:0',
            'description' => 'nullable|string'
        ]);

        $oldValues = $product->toArray();
        $product->update($validated);

        AuditLog::create([
            'user_id' => $request->user()->id ?? 1,
            'action' => 'UPDATE_PRODUCT',
            'model_type' => 'Product',
            'model_id' => $product->id,
            'old_values' => $oldValues,
            'new_values' => $product->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json($product);
    }

    public function destroy(Request $request, Product $product)
    {
        $oldValues = $product->toArray();
        $product->delete();

        AuditLog::create([
            'user_id' => $request->user()->id ?? 1,
            'action' => 'DELETE_PRODUCT',
            'model_type' => 'Product',
            'model_id' => $product->id,
            'old_values' => $oldValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json(null, 204);
    }
}
