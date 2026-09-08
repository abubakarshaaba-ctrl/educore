<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentTransfer extends Model
{
    protected $table = 'student_transfers';

    protected $fillable = [
        'from_tenant_id',
        'to_tenant_id',
        'student_id',
        'student_name',
        'admission_number',
        'status',
        'reason',
        'requested_by',
        'approved_at',
    ];

    /**
     * Cross-school transfers do not have a single tenant_id, so the normal
     * BaseTenantModel scope cannot be used. Route binding is nevertheless
     * restricted to a transfer where the signed-in school is either sender or
     * receiver, preventing arbitrary transfer-id enumeration.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();
        $query = static::query()->where($field, $value);
        $user = auth()->user();

        if ($user && !$user->isSuperAdmin()) {
            if (!$user->tenant_id) {
                return null;
            }
            $query->where(function ($scope) use ($user): void {
                $scope->where('from_tenant_id', $user->tenant_id)
                    ->orWhere('to_tenant_id', $user->tenant_id);
            });
        }

        return $query->first();
    }
}
