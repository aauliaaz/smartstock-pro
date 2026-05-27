<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::with(['role:id,code,name', 'warehouse:id,code,name'])
            ->when($request->input('search'), fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")
            )
            ->when($request->input('role_id'), fn ($q, $rid) => $q->where('role_id', $rid))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => User::with(['role', 'warehouse'])->findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
            'role_id' => ['required', 'exists:roles,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'is_active' => ['boolean'],
        ], [
            'password.regex' => 'Password harus mengandung huruf dan angka',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        $user = User::create($data);
        AuditLog::record('CREATE', $user, null, ['email' => $user->email, 'role_id' => $user->role_id]);

        return response()->json([
            'success' => true,
            'data' => $user->load(['role', 'warehouse']),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', "unique:users,email,{$id}"],
            'role_id' => ['sometimes', 'exists:roles,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $old = $user->only(['name', 'email', 'role_id', 'warehouse_id', 'is_active']);
        $user->update($data);
        AuditLog::record('UPDATE', $user, $old, $data);

        return response()->json([
            'success' => true,
            'data' => $user->fresh()->load(['role', 'warehouse']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa menghapus akun sendiri.'], 422);
        }
        AuditLog::record('DEACTIVATE', $user, ['is_active' => $user->is_active]);
        $user->update(['is_active' => false]);
        return response()->json(['success' => true, 'message' => 'User dinonaktifkan.']);
    }

    public function resetPassword(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $newPassword = Str::random(12).'1A';
        $user->update(['password' => $newPassword]);
        AuditLog::record('RESET_PASSWORD', $user);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil di-reset.',
            'data' => ['temporary_password' => $newPassword],
        ]);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => \App\Models\Role::orderBy('id')->get(),
        ]);
    }
}
