<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = ErrorLog::with('user:id,name')
            ->when($request->input('severity'), fn ($q, $v) => $q->where('severity', $v))
            ->when($request->boolean('unresolved_only'), fn ($q) => $q->where('is_resolved', false))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        $stats = [
            'critical' => ErrorLog::where('severity', 'CRITICAL')->where('is_resolved', false)->count(),
            'warning' => ErrorLog::where('severity', 'WARNING')->where('is_resolved', false)->count(),
            'info' => ErrorLog::where('severity', 'INFO')->where('is_resolved', false)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
                'stats' => $stats,
            ],
        ]);
    }

    public function resolve(int $id): JsonResponse
    {
        $log = ErrorLog::findOrFail($id);
        $log->update(['is_resolved' => true]);
        return response()->json(['success' => true, 'data' => $log]);
    }

    public function destroy(int $id): JsonResponse
    {
        ErrorLog::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Error log dihapus.']);
    }
}
