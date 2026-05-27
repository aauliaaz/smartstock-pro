<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Role;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function __construct(
        private TransferService $transfers,
        private NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = StockTransfer::with([
                'fromWarehouse:id,code,name',
                'toWarehouse:id,code,name',
                'requester:id,name',
                'approver:id,name',
                'items.product:id,sku,name',
            ])
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('warehouse_id'), function ($q, $wid) {
                $q->where(fn ($w) => $w->where('from_warehouse_id', $wid)->orWhere('to_warehouse_id', $wid));
            });

        if ($user->isStaff() && $user->warehouse_id) {
            $query->where(function ($w) use ($user) {
                $w->where('from_warehouse_id', $user->warehouse_id)
                  ->orWhere('to_warehouse_id', $user->warehouse_id);
            });
        }

        $transfers = $query->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json([
            'success' => true,
            'data' => $transfers->items(),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $transfer = StockTransfer::with([
            'fromWarehouse', 'toWarehouse', 'requester', 'approver', 'receiver',
            'items.product:id,sku,name,unit',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $transfer]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'from_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $fromWh = $data['from_warehouse_id'] ?? $user->warehouse_id;
        if (! $fromWh) {
            return response()->json(['success' => false, 'message' => 'Gudang asal harus ditentukan.'], 422);
        }

        if ($user->isStaff() && $fromWh != $user->warehouse_id) {
            return response()->json(['success' => false, 'message' => 'Staf hanya bisa request dari gudang sendiri.'], 403);
        }

        try {
            $transfer = $this->transfers->createTransfer(
                (int) $fromWh,
                (int) $data['to_warehouse_id'],
                $data['items'],
                $user,
                $data['reason'] ?? null,
            );

            // Notif ke manajer/admin
            $managers = User::whereHas('role', fn ($q) => $q->whereIn('code', [Role::ADMIN, Role::MANAGER]))
                ->where('is_active', true)
                ->pluck('id')->all();
            $this->notifications->broadcast(
                $managers,
                Notification::TYPE_TRANSFER,
                "Transfer baru perlu approval: {$transfer->transfer_code}",
                "Dari {$transfer->fromWarehouse->name} ke {$transfer->toWarehouse->name}",
                'INFO',
                ['transfer_id' => $transfer->id],
            );
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Transfer dibuat.', 'data' => $transfer], 201);
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);
        try {
            $transfer = $this->transfers->approve($transfer, $request->user());
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        return response()->json(['success' => true, 'message' => 'Transfer disetujui.', 'data' => $transfer]);
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $data = $request->validate(['reject_note' => ['required', 'string', 'min:5']]);
        $transfer = StockTransfer::findOrFail($id);
        try {
            $transfer = $this->transfers->reject($transfer, $request->user(), $data['reject_note']);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        return response()->json(['success' => true, 'message' => 'Transfer ditolak.', 'data' => $transfer]);
    }

    public function ship(int $id, Request $request): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);
        try {
            $transfer = $this->transfers->ship($transfer, $request->user());
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        return response()->json(['success' => true, 'data' => $transfer]);
    }

    public function receive(int $id, Request $request): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);
        try {
            $transfer = $this->transfers->receive($transfer, $request->user());
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        return response()->json(['success' => true, 'message' => 'Transfer diterima.', 'data' => $transfer]);
    }

    public function cancel(int $id, Request $request): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);
        try {
            $transfer = $this->transfers->cancel($transfer, $request->user());
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        return response()->json(['success' => true, 'message' => 'Transfer dibatalkan.', 'data' => $transfer]);
    }
}
