<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassCoverageAssignment extends BaseTenantModel
{
    protected $table = 'class_coverage_assignments';

    protected $fillable = [
        'tenant_id', 'timetable_period_id', 'class_arm_id', 'subject_id',
        'absent_teacher_id', 'covering_teacher_id', 'coverage_date', 'status', 'notes', 'assigned_by',
    ];

    protected function casts(): array
    {
        return ['coverage_date' => 'date'];
    }

    public function timetablePeriod(): BelongsTo
    {
        return $this->belongsTo(TimetablePeriod::class);
    }

    public function classArm(): BelongsTo
    {
        return $this->belongsTo(ClassArm::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function absentTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'absent_teacher_id');
    }

    public function coveringTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'covering_teacher_id');
    }
}
