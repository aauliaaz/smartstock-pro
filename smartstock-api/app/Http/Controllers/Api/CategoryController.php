<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::withCount('products')
            ->when($request->input('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Category::withCount('products')->findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
        ]);
        $category = Category::create($data);
        AuditLog::record('CREATE', $category, null, $data);
        return response()->json(['success' => true, 'data' => $category], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
        ]);
        $old = $category->toArray();
        $category->update($data);
        AuditLog::record('UPDATE', $category, $old, $data);
        return response()->json(['success' => true, 'data' => $category]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori masih memiliki produk, tidak bisa dihapus.',
            ], 422);
        }
        AuditLog::record('DELETE', $category, $category->toArray());
        $category->delete();
        return response()->json(['success' => true, 'message' => 'Kategori dihapus.']);
    }
}
