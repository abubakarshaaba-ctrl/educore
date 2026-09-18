<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumEnrolment extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id','parallel_curriculum_id','parallel_curriculum_class_id',
        'parallel_curriculum_class_arm_id','student_id','session_id','is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id');
    }

    public function curriculumClass(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculumClass::class, 'parallel_curriculum_class_id');
    }

    public function curriculumClassArm(): BelongsTo
    {
        return $this->belongsTo(
            ParallelCurriculumClassArm::class,
            'parallel_curriculum_class_arm_id'
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }
}
