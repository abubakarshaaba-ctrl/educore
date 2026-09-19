<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumAttendanceRecord extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_id',
        'parallel_curriculum_class_id',
        'parallel_curriculum_class_arm_id',
        'parallel_curriculum_enrolment_id',
        'student_id',
        'term_id',
        'marked_by',
        'attendance_date',
        'status',
        'remark',
    ];

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
    }

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

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculumEnrolment::class, 'parallel_curriculum_enrolment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
