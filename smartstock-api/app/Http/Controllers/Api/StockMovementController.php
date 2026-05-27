<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(private StockService $stocks) {}

    public function index(Request $request): JsonResponse
    {
        $query = StockMovement::with([
                'product:id,sku,name,unit',
                'warehouse:id,code,name',
                'supplier:id,name',
                'user:id,name',
            ])
            ->when($request->input('product_id'), fn ($q, $v) => $q->where('product_id', $v))
            ->when($request->input('warehouse_id'), fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($request->input('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->input('date_from'), fn ($q, $v) => $q->whereDate('movement_date', '>=', $v))
            ->when($request->input('date_to'), fn ($q, $v) => $q->whereDate('movement_date', '<=', $v));

        // STF only sees own warehouse
        $user = $request->user();
        if ($user->isStaff() && $user->warehouse_id) {
            $query->where('warehouse_id', $user->warehouse_id);
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $movements = $query->orderByDesc('movement_date')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $movements->items(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'total' => $movements->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'type' => ['required', 'in:IN,OUT,ADJUSTMENT'],
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'movement_date' => ['nullable', 'date'],
        ]);

        // Enforce warehouse access for STF
        if ($user->isStaff() && $user->warehouse_id && $data['warehouse_id'] != $user->warehouse_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya bisa input transaksi di gudang sendiri.',
            ], 403);
        }

        // For IN/OUT, qty must be positive
        if (in_array($data['type'], ['IN', 'OUT']) && $data['quantity'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Quantity harus > 0',
                'errors' => ['quantity' => ['Quantity harus lebih dari 0']],
            ], 422);
        }

        $data['user_id'] = $user->id;
        $data['quantity'] = abs($data['quantity']);

        try {
            $movement = $this->stocks->recordMovement($data);
            $this->stocks->checkAndAlert((int) $data['product_id'], (int) $data['warehouse_id']);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dicatat.',
            'data' => $movement->load(['product', 'warehouse', 'supplier']),
        ], 201);
    }
}
