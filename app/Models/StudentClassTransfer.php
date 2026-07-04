<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClassTransfer extends BaseTenantModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'tenant_id',
        'student_id',
        'academic_session_id',
        'term_id',
        'from_class_arm_id',
        'to_class_arm_id',
        'effective_date',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'completed_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'supporting_document',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function session(): BelongsTo
    {
        return $this->academicSession();
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function fromClassArm(): BelongsTo
    {
        return $this->belongsTo(ClassArm::class, 'from_class_arm_id');
    }

    public function toClassArm(): BelongsTo
    {
        return $this->belongsTo(ClassArm::class, 'to_class_arm_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED,
        ], true);
    }
}
