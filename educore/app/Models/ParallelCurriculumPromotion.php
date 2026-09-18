<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumPromotion extends BaseTenantModel
{
    public const DECISION_PROMOTED = 'promoted';
    public const DECISION_REPEAT = 'repeat';
    public const DECISION_RETAIN = 'retain';
    public const DECISION_GRADUATED = 'graduated';

    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_id',
        'student_id',
        'source_enrolment_id',
        'source_session_id',
        'target_session_id',
        'source_class_id',
        'source_arm_id',
        'destination_class_id',
        'destination_arm_id',
        'decision',
        'average_score',
        'failed_subjects',
        'reason',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'average_score' => 'float',
            'failed_subjects' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function sourceEnrolment(): BelongsTo { return $this->belongsTo(ParallelCurriculumEnrolment::class, 'source_enrolment_id'); }
    public function sourceSession(): BelongsTo { return $this->belongsTo(AcademicSession::class, 'source_session_id'); }
    public function targetSession(): BelongsTo { return $this->belongsTo(AcademicSession::class, 'target_session_id'); }
    public function sourceClass(): BelongsTo { return $this->belongsTo(ParallelCurriculumClass::class, 'source_class_id'); }
    public function destinationClass(): BelongsTo { return $this->belongsTo(ParallelCurriculumClass::class, 'destination_class_id'); }
    public function sourceArm(): BelongsTo { return $this->belongsTo(ParallelCurriculumClassArm::class, 'source_arm_id'); }
    public function destinationArm(): BelongsTo { return $this->belongsTo(ParallelCurriculumClassArm::class, 'destination_arm_id'); }
    public function processedBy(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}
