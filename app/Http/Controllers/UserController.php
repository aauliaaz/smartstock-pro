<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        return User::with('role')->paginate(15);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role_id' => 'required|exists:roles,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        AuditLog::create([
            'user_id' => $request->user()->id ?? 1,
            'action' => 'CREATE_USER',
            'model_type' => 'User',
            'model_id' => $user->id,
            'new_values' => $user->only(['name', 'email', 'role_id']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json($user->load('role'), 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $user->id,
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
            'role_id' => 'exists:roles,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $oldValues = $user->only(['name', 'email', 'role_id']);
        $user->update($validated);

        AuditLog::create([
            'user_id' => $request->user()->id ?? 1,
            'action' => 'UPDATE_USER',
            'model_type' => 'User',
            'model_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $user->only(['name', 'email', 'role_id']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json($user->load('role'));
    }

    public function destroy(Request $request, User $user)
    {
        $oldValues = $user->only(['name', 'email', 'role_id']);
        $user->delete();

        AuditLog::create([
            'user_id' => $request->user()->id ?? 1,
            'action' => 'DELETE_USER',
            'model_type' => 'User',
            'model_id' => $user->id,
            'old_values' => $oldValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json(null, 204);
    }
}
