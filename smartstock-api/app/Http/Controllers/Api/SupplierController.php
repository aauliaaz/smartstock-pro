<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $suppliers = Supplier::query()
            ->when($request->input('search'), fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%")
            )
            ->orderBy('name')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json([
            'success' => true,
            'data' => $suppliers->items(),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'total' => $suppliers->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Supplier::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
        $supplier = Supplier::create($data);
        AuditLog::record('CREATE', $supplier, null, $data);
        return response()->json(['success' => true, 'data' => $supplier], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', "unique:suppliers,code,{$id}"],
            'name' => ['sometimes', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $old = $supplier->toArray();
        $supplier->update($data);
        AuditLog::record('UPDATE', $supplier, $old, $data);
        return response()->json(['success' => true, 'data' => $supplier]);
    }

    public function destroy(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        AuditLog::record('DELETE', $supplier, $supplier->toArray());
        $supplier->update(['is_active' => false]);
        return response()->json(['success' => true, 'message' => 'Supplier dinonaktifkan.']);
    }
}
