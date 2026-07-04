<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffWorkHistory extends BaseTenantModel
{
    public const CHANGE_APPOINTMENT = 'appointment';
    public const CHANGE_CONFIRMATION = 'confirmation';
    public const CHANGE_PROMOTION = 'promotion';
    public const CHANGE_TRANSFER = 'transfer';
    public const CHANGE_REASSIGNMENT = 'reassignment';
    public const CHANGE_ACTING_APPOINTMENT = 'acting_appointment';
    public const CHANGE_DEMOTION = 'demotion';
    public const CHANGE_SUSPENSION = 'suspension';
    public const CHANGE_REINSTATEMENT = 'reinstatement';
    public const CHANGE_RESIGNATION = 'resignation';
    public const CHANGE_TERMINATION = 'termination';
    public const CHANGE_RETIREMENT = 'retirement';
    public const CHANGE_EXIT = 'exit';

    public const CHANGE_TYPES = [
        self::CHANGE_APPOINTMENT,
        self::CHANGE_CONFIRMATION,
        self::CHANGE_PROMOTION,
        self::CHANGE_TRANSFER,
        self::CHANGE_REASSIGNMENT,
        self::CHANGE_ACTING_APPOINTMENT,
        self::CHANGE_DEMOTION,
        self::CHANGE_SUSPENSION,
        self::CHANGE_REINSTATEMENT,
        self::CHANGE_RESIGNATION,
        self::CHANGE_TERMINATION,
        self::CHANGE_RETIREMENT,
        self::CHANGE_EXIT,
    ];

    protected $fillable = [
        'tenant_id',
        'user_id',
        'position_title',
        'department_name',
        'employment_type',
        'functional_role',
        'grade_level',
        'appointment_type',
        'start_date',
        'end_date',
        'change_type',
        'reason',
        'document_path',
        'recorded_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->staff();
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('end_date');
    }
}
