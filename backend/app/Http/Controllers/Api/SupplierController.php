<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [
            'owner',
            'warehouse_admin',
            'admin_salam_sehat',
            'admin_badan_sehat',
            'supplier',
        ], true), 403);
        $suppliers = Supplier::query()->where('is_active', true)->latest();

        if ($user->role === 'supplier') {
            abort_unless($user->supplier_id, 403, 'Akun supplier belum terhubung ke data supplier.');
            $suppliers->whereKey($user->supplier_id);
        }

        return response()->json(['data' => $suppliers->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeOwner($request->user());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255', 'unique:suppliers,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json(['data' => Supplier::create($data)], 201);
    }

    public function show(Request $request, Supplier $supplier): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'owner' || ($user->role === 'supplier' && $user->supplier_id === $supplier->id), 404);

        return response()->json(['data' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorizeOwner($request->user());
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($supplier->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $supplier->update($data);

        return response()->json(['data' => $supplier->fresh()]);
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorizeOwner($request->user());
        $supplier->update(['is_active' => false]);

        return response()->json(['message' => 'Supplier dinonaktifkan.']);
    }

    private function authorizeOwner(User $user): void
    {
        abort_unless($user->role === 'owner', 403, 'Hanya owner yang dapat mengelola data supplier.');
    }
}
