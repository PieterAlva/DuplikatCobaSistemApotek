<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function setupStatus(): JsonResponse
    {
        return response()->json(['needs_setup' => ! User::exists()]);
    }

    public function setupOwner(Request $request): JsonResponse
    {
        if (User::exists()) {
            abort(409, 'Pengaturan awal sudah selesai.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $owner = DB::transaction(function () use ($data) {
            Pharmacy::firstOrCreate(
                ['slug' => 'apotek-salam-sehat'],
                ['name' => 'Apotek Salam Sehat', 'is_active' => true],
            );
            Pharmacy::firstOrCreate(
                ['slug' => 'apotek-badan-sehat'],
                ['name' => 'Apotek Badan Sehat', 'is_active' => true],
            );

            return User::create([...$data, 'role' => 'owner', 'is_active' => true]);
        });

        Auth::login($owner);
        $request->session()->regenerate();

        return response()->json(['user' => $owner->load(['pharmacy', 'supplier'])], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => ['Email atau kata sandi tidak sesuai.']]);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'Akun dinonaktifkan. Hubungi owner apotek.');
        }

        return response()->json(['user' => $user->load(['pharmacy', 'supplier'])]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->load(['pharmacy', 'supplier'])]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Anda berhasil keluar.']);
    }
}
