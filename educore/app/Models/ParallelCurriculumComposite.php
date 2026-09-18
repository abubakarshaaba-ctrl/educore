<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumComposite extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id','parallel_curriculum_id','parallel_curriculum_integration_id',
        'parallel_curriculum_class_id','conventional_class_arm_id','student_id',
        'destination_subject_id','term_id','session_id','average_score',
        'subject_count','completed_subject_count','subject_breakdown',
        'sync_status','sync_message','computed_at',
    ];

    protected function casts(): array
    {
        return [
            'average_score' => 'float',
            'subject_count' => 'integer',
            'completed_subject_count' => 'integer',
            'subject_breakdown' => 'array',
            'computed_at' => 'datetime',
        ];
    }

    public function curriculum(): BelongsTo { return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id'); }
    public function integration(): BelongsTo { return $this->belongsTo(ParallelCurriculumIntegration::class, 'parallel_curriculum_integration_id'); }
    public function curriculumClass(): BelongsTo { return $this->belongsTo(ParallelCurriculumClass::class, 'parallel_curriculum_class_id'); }
    public function conventionalClassArm(): BelongsTo { return $this->belongsTo(ClassArm::class, 'conventional_class_arm_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function destinationSubject(): BelongsTo { return $this->belongsTo(Subject::class, 'destination_subject_id'); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function session(): BelongsTo { return $this->belongsTo(AcademicSession::class, 'session_id'); }
}
