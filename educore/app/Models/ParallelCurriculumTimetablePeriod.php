<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumTimetablePeriod extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_id',
        'parallel_curriculum_class_id',
        'parallel_curriculum_class_arm_id',
        'parallel_curriculum_subject_id',
        'teacher_id',
        'session_id',
        'day_of_week',
        'start_time',
        'end_time',
        'venue',
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id');
    }

    public function curriculumClass(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculumClass::class, 'parallel_curriculum_class_id');
    }

    public function classArm(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculumClassArm::class, 'parallel_curriculum_class_arm_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculumSubject::class, 'parallel_curriculum_subject_id');
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
