<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumArmSubjectTeacher extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_class_id',
        'parallel_curriculum_class_arm_id',
        'parallel_curriculum_subject_id',
        'teacher_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
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
}
