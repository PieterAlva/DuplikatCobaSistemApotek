<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    private const SUMMARY_KEYS = [
        'id',
        'sku',
        'role',
        'status',
        'quantity',
        'quantity_received',
        'quantity_ordered',
        'quantity_delta',
        'stock_before',
        'stock_after',
        'item_count',
        'total',
        'is_active',
    ];

    public static function record(
        Request $request,
        string $action,
        ?int $pharmacyId,
        Model|string $auditable,
        ?int $auditableId,
        array $oldValues = [],
        array $newValues = [],
    ): AuditLog {
        return AuditLog::create([
            'pharmacy_id' => $pharmacyId,
            'user_id' => $request->user()?->id,
            'action' => $action,
            'auditable_type' => $auditable instanceof Model ? $auditable::class : $auditable,
            'auditable_id' => $auditable instanceof Model ? $auditable->getKey() : $auditableId,
            'old_values' => self::summary($oldValues),
            'new_values' => self::summary($newValues),
            'ip_address' => $request->ip(),
        ]);
    }

    private static function summary(array $values): array
    {
        return array_intersect_key($values, array_flip(self::SUMMARY_KEYS));
    }
}
