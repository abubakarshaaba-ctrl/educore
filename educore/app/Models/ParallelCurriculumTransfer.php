<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumTransfer extends BaseTenantModel
{
    public const TYPE_INTRA_CLASS = 'intra_class';
    public const TYPE_INTER_CLASS = 'inter_class';

    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_id',
        'student_id',
        'enrolment_id',
        'session_id',
        'from_class_id',
        'from_arm_id',
        'to_class_id',
        'to_arm_id',
        'movement_type',
        'reason',
        'effective_date',
        'status',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'processed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function enrolment(): BelongsTo { return $this->belongsTo(ParallelCurriculumEnrolment::class, 'enrolment_id'); }
    public function session(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function fromClass(): BelongsTo { return $this->belongsTo(ParallelCurriculumClass::class, 'from_class_id'); }
    public function toClass(): BelongsTo { return $this->belongsTo(ParallelCurriculumClass::class, 'to_class_id'); }
    public function fromArm(): BelongsTo { return $this->belongsTo(ParallelCurriculumClassArm::class, 'from_arm_id'); }
    public function toArm(): BelongsTo { return $this->belongsTo(ParallelCurriculumClassArm::class, 'to_arm_id'); }
    public function processedBy(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}
