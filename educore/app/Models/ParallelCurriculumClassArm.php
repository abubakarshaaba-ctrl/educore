<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParallelCurriculumClassArm extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_class_id',
        'name',
        'code',
        'capacity',
        'teaching_assignment_mode',
        'class_teacher_id',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'class_teacher_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function curriculumClass(): BelongsTo
    {
        return $this->belongsTo(
            ParallelCurriculumClass::class,
            'parallel_curriculum_class_id'
        );
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(
            ParallelCurriculumEnrolment::class,
            'parallel_curriculum_class_arm_id'
        );
    }

    public function subjectTeachers(): HasMany
    {
        return $this->hasMany(
            ParallelCurriculumArmSubjectTeacher::class,
            'parallel_curriculum_class_arm_id'
        );
    }

    public function classTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'class_teacher_id');
    }

    public function usesClassTeacherModel(): bool
    {
        return ($this->teaching_assignment_mode ?: 'subject_based') === 'class_teacher';
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->curriculumClass?->name ?? '').' '.$this->name);
    }
}
