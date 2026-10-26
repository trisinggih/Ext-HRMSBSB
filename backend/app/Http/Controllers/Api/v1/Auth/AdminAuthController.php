<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'portal_type' => 'nullable|in:supervisor,hrd',
        ]);

        if ($request->portal_type === 'supervisor') {
            throw ValidationException::withMessages([
                'email' => ['Akun Administrator HRD silakan masuk melalui Portal HRD (/hrms/login).'],
            ]);
        }

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak valid.'],
            ]);
        }

        if (!$admin->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun nonaktif.'],
            ]);
        }

        $token = $admin->createToken('admin-auth', ['*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role,
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
            'data' => null,
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $admin = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'permissions' => $admin->roles->flatMap->permissions->pluck('slug')->unique()->values(),
            ],
        ]);
    }
}
