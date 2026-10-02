<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Pharmacy;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeCustomerAccess($user);
        $filters = $request->validate([
            'pharmacy_id' => ['nullable', 'integer', 'exists:pharmacies,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $customers = Customer::query()->with('pharmacy:id,name')->latest();
        if ($this->branchScoped($user)) {
            $pharmacyId = $this->branchId($user);
            abort_if(isset($filters['pharmacy_id']) && (int) $filters['pharmacy_id'] !== $pharmacyId, 404);
            $customers->where('pharmacy_id', $pharmacyId);
        } elseif (isset($filters['pharmacy_id'])) {
            $customers->where('pharmacy_id', $filters['pharmacy_id']);
        }
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $customers->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        return response()->json(['data' => $customers->paginate($filters['per_page'] ?? 30)]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeCustomerAccess($user);
        $data = $request->validate([
            'pharmacy_id' => [$this->branchScoped($user) ? 'nullable' : 'required', 'integer', 'exists:pharmacies,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $pharmacyId = $this->branchScoped($user) ? $this->branchId($user) : (int) $data['pharmacy_id'];
        abort_if(isset($data['pharmacy_id']) && (int) $data['pharmacy_id'] !== $pharmacyId, 404);

        $customer = Customer::create([...$data, 'pharmacy_id' => $pharmacyId]);
        AuditLogger::record($request, 'customer.created', $pharmacyId, $customer, $customer->id, [], ['id' => $customer->id]);

        return response()->json(['data' => $customer->load('pharmacy:id,name')], 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomerAccess($request->user());
        $this->authorizeCustomerBranch($request->user(), $customer);

        return response()->json(['data' => $customer->load('pharmacy:id,name')]);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $user = $request->user();
        $this->authorizeCustomerAccess($user);
        $this->authorizeCustomerBranch($user, $customer);
        $data = $request->validate([
            'pharmacy_id' => ['prohibited'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $customer->update($data);
        AuditLogger::record(
            $request,
            'customer.updated',
            $customer->pharmacy_id,
            $customer,
            $customer->id,
            ['id' => $customer->id],
            ['id' => $customer->id],
        );

        return response()->json(['data' => $customer->fresh()->load('pharmacy:id,name')]);
    }

    private function authorizeCustomerAccess(User $user): void
    {
        abort_unless(
            in_array($user->role, ['owner', 'admin_salam_sehat', 'admin_badan_sehat', 'cashier'], true),
            403,
            'Role Anda tidak memiliki akses ke data pelanggan.',
        );
        if ($this->branchScoped($user)) {
            abort_unless($this->branchId($user), 403, 'Akun belum terhubung ke apotek.');
        }
    }

    private function branchScoped(User $user): bool
    {
        return $user->role !== 'owner';
    }

    private function branchId(User $user): int
    {
        if ($user->pharmacy_id) {
            return $user->pharmacy_id;
        }

        if (isset(User::PHARMACY_ROLES[$user->role])) {
            $id = Pharmacy::query()->where('slug', User::PHARMACY_ROLES[$user->role])->value('id');
            abort_unless($id, 404);

            return $id;
        }

        abort(404, 'Akun belum terhubung ke apotek.');
    }

    private function authorizeCustomerBranch(User $user, Customer $customer): void
    {
        if ($this->branchScoped($user)) {
            abort_unless($customer->pharmacy_id === $this->branchId($user), 404);
        }
    }
}
