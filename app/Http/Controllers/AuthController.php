<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ── تسجيل حساب جديد ──────────────────────

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        $token = $user->createToken('gfr_token')->plainTextToken;

        return response()->json([
            'message' => 'تم إنشاء الحساب بنجاح',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // ── تسجيل الدخول ─────────────────────────

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['البريد الإلكتروني أو كلمة المرور غير صحيحة'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('gfr_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // ── تسجيل الخروج ─────────────────────────

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    // ── بيانات المستخدم الحالي ────────────────

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    // ── تحديث الملف الشخصي ───────────────────

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'specialty' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:1000',
        ]);

        $request->user()->update($request->only([
            'name',
            'specialty',
            'bio'
        ]));

        return response()->json([
            'message' => 'تم تحديث الملف الشخصي',
            'user' => $request->user()->fresh(),
        ]);
    }
}