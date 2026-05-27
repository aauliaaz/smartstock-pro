<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', $request->user()->id);
        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }
        $notifs = $query->orderByDesc('created_at')->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json([
            'success' => true,
            'data' => $notifs->items(),
            'meta' => [
                'current_page' => $notifs->currentPage(),
                'last_page' => $notifs->lastPage(),
                'total' => $notifs->total(),
                'unread_count' => Notification::where('user_id', $request->user()->id)->whereNull('read_at')->count(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'count' => Notification::where('user_id', $request->user()->id)->whereNull('read_at')->count(),
            ],
        ]);
    }

    public function markRead(int $id, Request $request): JsonResponse
    {
        $notif = Notification::where('user_id', $request->user()->id)->findOrFail($id);
        $notif->markRead();
        return response()->json(['success' => true, 'data' => $notif]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return response()->json(['success' => true, 'message' => "{$count} notifikasi ditandai sudah dibaca."]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)->findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Notifikasi dihapus.']);
    }
}
