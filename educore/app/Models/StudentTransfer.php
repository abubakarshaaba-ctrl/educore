<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransfer extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REJECTED,
        self::STATUS_COMPLETED,
    ];

    protected $table = 'student_transfers';

    protected $fillable = [
        'from_tenant_id',
        'to_tenant_id',
        'student_id',
        'destination_student_id',
        'student_name',
        'admission_number',
        'status',
        'reason',
        'requested_by',
        'approved_by',
        'approved_at',
        'completed_at',
        'rejected_by',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function sourceStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function destinationStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'destination_student_id');
    }

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
