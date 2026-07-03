<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends BaseTenantModel
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_LEFT = 'left';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_TRANSFERRED_OUT = 'transferred_out';
    public const STATUS_GRADUATED = 'graduated';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'class_arm_id',
        'session_id',
        'term_id',
        'start_date',
        'end_date',
        'is_current',
        'status',
        'created_by',
        'ended_by',
        'ended_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classArm(): BelongsTo
    {
        return $this->belongsTo(ClassArm::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }
}
