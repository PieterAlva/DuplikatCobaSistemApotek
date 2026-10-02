<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor->canManageUsers(), 403, 'Role Anda tidak dapat mengelola pengguna.');

        $users = User::query()->with(['pharmacy:id,name', 'supplier:id,name'])->latest();
        $this->scopeToActor($users, $actor);
        $roles = $actor->role === 'owner'
            ? User::ROLES
            : array_values(array_unique([$actor->role, 'cashier']));

        return response()->json([
            'data' => $users->get(),
            'roles' => $roles,
            'pharmacies' => Pharmacy::query()->where('is_active', true)->get(['id', 'name', 'slug']),
            'suppliers' => $actor->role === 'owner'
                ? Supplier::query()->where('is_active', true)->get(['id', 'name'])
                : [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor->canManageUsers(), 403, 'Role Anda tidak dapat mengelola pengguna.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
            'role' => ['required', Rule::in(User::ROLES)],
            'pharmacy_id' => ['nullable', 'integer', 'exists:pharmacies,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->ensureAssignableRole($actor, $data['role']);
        $data = $this->assignScope($data, $actor);

        $created = User::create($data);
        AuditLogger::record(
            $request,
            'user.created',
            $created->pharmacy_id,
            $created,
            $created->id,
            [],
            ['id' => $created->id, 'role' => $created->role, 'is_active' => $created->is_active],
        );

        return response()->json([
            'data' => $created->load(['pharmacy:id,name', 'supplier:id,name']),
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorizeTarget($request->user(), $user);

        return response()->json(['data' => $user->load(['pharmacy:id,name', 'supplier:id,name'])]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $this->authorizeTarget($actor, $user);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'required', 'string', 'min:10', 'confirmed'],
            'role' => ['sometimes', 'required', Rule::in(User::ROLES)],
            'pharmacy_id' => ['nullable', 'integer', 'exists:pharmacies,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['role'])) {
            $this->ensureAssignableRole($actor, $data['role']);
            $data = $this->assignScope($data, $actor);
        } elseif ($actor->role !== 'owner') {
            unset($data['pharmacy_id'], $data['supplier_id']);
        }

        if ($user->role === 'owner' && $user->is_active && (
            ($data['role'] ?? $user->role) !== 'owner' || ($data['is_active'] ?? true) === false
        )) {
            abort_if(User::query()->where('role', 'owner')->where('is_active', true)->count() <= 1, 422, 'Owner aktif terakhir tidak dapat dinonaktifkan atau diubah rolenya.');
        }

        $oldRole = $user->role;
        $oldActive = $user->is_active;
        $user->update($data);
        AuditLogger::record(
            $request,
            'user.updated',
            $user->pharmacy_id,
            $user,
            $user->id,
            ['id' => $user->id, 'role' => $oldRole, 'is_active' => $oldActive],
            ['id' => $user->id, 'role' => $user->role, 'is_active' => $user->is_active],
        );

        return response()->json(['data' => $user->fresh()->load(['pharmacy:id,name', 'supplier:id,name'])]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeTarget($request->user(), $user);
        abort_if($request->user()->is($user), 422, 'Anda tidak dapat menghapus akun sendiri.');
        abort_if($user->role === 'owner' && $user->is_active
            && User::query()->where('role', 'owner')->where('is_active', true)->count() <= 1,
            422,
            'Owner aktif terakhir tidak dapat dihapus.');

        $deletedId = $user->id;
        $branchId = $user->pharmacy_id;
        $deletedRole = $user->role;
        $deletedActive = $user->is_active;
        AuditLogger::record(
            $request,
            'user.deleted',
            $branchId,
            User::class,
            $deletedId,
            ['id' => $deletedId, 'role' => $deletedRole, 'is_active' => $deletedActive],
        );
        $user->delete();

        return response()->json(['message' => 'Pengguna berhasil dihapus.']);
    }

    private function scopeToActor(Builder $query, User $actor): void
    {
        if ($actor->role !== 'owner') {
            $query->where('pharmacy_id', $actor->pharmacy_id)->where('role', '!=', 'owner');
        }
    }

    private function authorizeTarget(User $actor, User $target): void
    {
        abort_unless($actor->canManageUsers(), 403, 'Role Anda tidak dapat mengelola pengguna.');
        abort_unless($actor->role === 'owner' || (
            $target->pharmacy_id !== null
            && $target->pharmacy_id === $actor->pharmacy_id
            && $target->role !== 'owner'
        ), 404);
    }

    private function ensureAssignableRole(User $actor, string $role): void
    {
        abort_unless(
            $actor->role === 'owner' || $role === $actor->role || $role === 'cashier',
            403,
            'Anda tidak dapat menetapkan role tersebut.',
        );
    }

    private function assignScope(array $data, User $actor): array
    {
        $role = $data['role'] ?? null;

        if ($role && isset(User::PHARMACY_ROLES[$role])) {
            $pharmacy = Pharmacy::query()->where('slug', User::PHARMACY_ROLES[$role])->firstOrFail();
            abort_unless($actor->role === 'owner' || $actor->pharmacy_id === $pharmacy->id, 403, 'Apotek tidak sesuai dengan cakupan akun Anda.');
            $data['pharmacy_id'] = $pharmacy->id;
            $data['supplier_id'] = null;
        } elseif ($role === 'supplier') {
            abort_unless($actor->role === 'owner', 403, 'Hanya owner yang dapat membuat akun supplier.');
            if (empty($data['supplier_id'])) {
                abort(422, 'Supplier harus dipilih untuk role supplier.');
            }
            $data['pharmacy_id'] = null;
        } elseif ($role === 'cashier') {
            if ($actor->role === 'owner') {
                abort_unless(! empty($data['pharmacy_id']), 422, 'Apotek harus dipilih untuk role kasir.');
            } else {
                abort_unless(
                    empty($data['pharmacy_id']) || (int) $data['pharmacy_id'] === $actor->pharmacy_id,
                    403,
                    'Apotek tidak sesuai dengan cakupan akun Anda.',
                );
                abort_unless($actor->pharmacy_id, 403, 'Akun Anda belum terhubung ke apotek.');
                $data['pharmacy_id'] = $actor->pharmacy_id;
            }
            $data['supplier_id'] = null;
        } elseif ($role === 'warehouse_admin') {
            $data['pharmacy_id'] = $actor->role === 'owner' ? ($data['pharmacy_id'] ?? null) : $actor->pharmacy_id;
            $data['supplier_id'] = null;
        } elseif ($role === 'owner') {
            abort_unless($actor->role === 'owner', 403, 'Hanya owner yang dapat menetapkan role owner.');
            $data['pharmacy_id'] = null;
            $data['supplier_id'] = null;
        }

        return $data;
    }
}
