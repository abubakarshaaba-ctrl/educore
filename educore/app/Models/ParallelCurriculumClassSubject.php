<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumClassSubject extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id','parallel_curriculum_class_id','parallel_curriculum_subject_id','teacher_id','is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function curriculumClass(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculumClass::class, 'parallel_curriculum_class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculumSubject::class, 'parallel_curriculum_subject_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function armTeacherAssignments(): HasMany
    {
        return $this->hasMany(
            ParallelCurriculumArmSubjectTeacher::class,
            'parallel_curriculum_subject_id',
            'parallel_curriculum_subject_id'
        )->whereColumn(
            'parallel_curriculum_arm_subject_teachers.parallel_curriculum_class_id',
            'parallel_curriculum_class_subjects.parallel_curriculum_class_id'
        );
    }
}
