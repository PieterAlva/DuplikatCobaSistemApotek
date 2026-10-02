<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MedicineRequest;
use App\Models\Pharmacy;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MedicineRequestController extends Controller
{
    private const STATUSES = ['requested', 'sourcing', 'ready', 'completed', 'cancelled'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $pharmacyId = $this->pharmacyId($user);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(self::STATUSES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $requests = MedicineRequest::query()
            ->with(['pharmacy:id,name', 'requester:id,name', 'processor:id,name'])
            ->where('pharmacy_id', $pharmacyId)
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(fn ($match) => $match
                    ->where('request_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('item_name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%"));
            })
            ->latest();

        return response()->json(['data' => $requests->paginate($filters['per_page'] ?? 10)]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $pharmacyId = $this->pharmacyId($user);
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'item_name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'unit' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (! empty($data['customer_id'])) {
            $customer = Customer::query()->where('pharmacy_id', $pharmacyId)->find($data['customer_id']);
            abort_unless($customer, 404, 'Pelanggan tidak ditemukan pada apotek ini.');
            $data['customer_name'] = $customer->name;
            $data['customer_phone'] = $customer->phone;
        }

        $medicineRequest = MedicineRequest::create([
            ...$data,
            'pharmacy_id' => $pharmacyId,
            'requested_by' => $user->id,
            'request_number' => 'MR-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
            'status' => 'requested',
        ]);
        AuditLogger::record(
            $request,
            'medicine_request.created',
            $pharmacyId,
            $medicineRequest,
            $medicineRequest->id,
            [],
            ['id' => $medicineRequest->id, 'status' => $medicineRequest->status, 'quantity' => $medicineRequest->quantity],
        );

        return response()->json([
            'data' => $medicineRequest->load(['pharmacy:id,name', 'requester:id,name', 'processor:id,name']),
        ], 201);
    }

    public function update(Request $request, MedicineRequest $medicineRequest): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['admin_salam_sehat', 'admin_badan_sehat'], true), 403);
        abort_unless($medicineRequest->pharmacy_id === $this->pharmacyId($user), 404);
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(self::STATUSES)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $allowedTransitions = [
            'requested' => ['sourcing', 'cancelled'],
            'sourcing' => ['ready', 'cancelled'],
            'ready' => ['sourcing', 'completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];
        if (! in_array($data['status'], $allowedTransitions[$medicineRequest->status], true)) {
            throw ValidationException::withMessages([
                'status' => ['Status permintaan tidak dapat diubah dari '.$medicineRequest->status.' ke '.$data['status'].'.'],
            ]);
        }

        $oldStatus = $medicineRequest->status;
        $medicineRequest->update([
            ...$data,
            'processed_by' => $user->id,
            'processed_at' => now(),
        ]);
        AuditLogger::record(
            $request,
            'medicine_request.status_updated',
            $medicineRequest->pharmacy_id,
            $medicineRequest,
            $medicineRequest->id,
            ['status' => $oldStatus],
            ['id' => $medicineRequest->id, 'status' => $medicineRequest->status],
        );

        return response()->json([
            'data' => $medicineRequest->fresh()->load(['pharmacy:id,name', 'requester:id,name', 'processor:id,name']),
        ]);
    }

    private function pharmacyId(User $user): int
    {
        abort_unless(in_array($user->role, ['admin_salam_sehat', 'admin_badan_sehat', 'cashier'], true), 403);

        if ($user->pharmacy_id) {
            return $user->pharmacy_id;
        }

        $slug = User::PHARMACY_ROLES[$user->role] ?? null;
        abort_unless($slug, 404, 'Akun belum terhubung ke apotek.');
        $id = Pharmacy::query()->where('slug', $slug)->value('id');
        abort_unless($id, 404, 'Apotek tidak ditemukan.');

        return $id;
    }
}
