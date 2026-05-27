<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $warehouses = Warehouse::with('manager:id,name')
            ->withCount('users')
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('code')
            ->get();

        $warehouses->each(function (Warehouse $w) {
            $w->total_stock = (int) $w->stocks()->sum('quantity');
            $w->total_products = $w->stocks()->where('quantity', '>', 0)->count();
        });

        return response()->json(['success' => true, 'data' => $warehouses]);
    }

    public function show(int $id): JsonResponse
    {
        $warehouse = Warehouse::with('manager')->findOrFail($id);
        $warehouse->total_stock = (int) $warehouse->stocks()->sum('quantity');
        return response()->json(['success' => true, 'data' => $warehouse]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity' => ['integer', 'min:0'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ]);
        $warehouse = Warehouse::create($data);
        AuditLog::record('CREATE', $warehouse, null, $data);
        return response()->json(['success' => true, 'data' => $warehouse], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:10', "unique:warehouses,code,{$id}"],
            'name' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity' => ['sometimes', 'integer', 'min:0'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $old = $warehouse->toArray();
        $warehouse->update($data);
        AuditLog::record('UPDATE', $warehouse, $old, $data);
        return response()->json(['success' => true, 'data' => $warehouse]);
    }

    public function destroy(int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        AuditLog::record('DELETE', $warehouse, $warehouse->toArray());
        $warehouse->update(['is_active' => false]);
        return response()->json(['success' => true, 'message' => 'Gudang dinonaktifkan.']);
    }
}
