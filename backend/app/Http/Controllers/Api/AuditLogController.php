<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            in_array($user->role, ['owner', 'warehouse_admin', 'admin_salam_sehat', 'admin_badan_sehat'], true),
            403,
            'Role Anda tidak memiliki akses ke audit log.',
        );

        $filters = $request->validate([
            'pharmacy_id' => ['nullable', 'integer', 'exists:pharmacies,id'],
            'action' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = AuditLog::query()->with(['user:id,name', 'pharmacy:id,name'])->latest('created_at');
        if (isset(User::PHARMACY_ROLES[$user->role])) {
            $pharmacyId = $user->pharmacy_id
                ?? Pharmacy::query()->where('slug', User::PHARMACY_ROLES[$user->role])->value('id');
            abort_unless($pharmacyId, 404);
            abort_if(isset($filters['pharmacy_id']) && (int) $filters['pharmacy_id'] !== (int) $pharmacyId, 404);
            $logs->where('pharmacy_id', $pharmacyId);
        } elseif (isset($filters['pharmacy_id'])) {
            $logs->where('pharmacy_id', $filters['pharmacy_id']);
        }
        if (! empty($filters['action'])) {
            $logs->where('action', $filters['action']);
        }

        return response()->json(['data' => $logs->paginate($filters['per_page'] ?? 30)]);
    }
}
