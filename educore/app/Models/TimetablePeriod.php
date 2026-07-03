<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetablePeriod extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'class_arm_id',
        'subject_id',
        'teacher_id',
        'session_id',
        'day_of_week',
        'start_time',
        'end_time',
        'venue',
    ];

    public function classArm(): BelongsTo
    {
        return $this->belongsTo(ClassArm::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }
}
