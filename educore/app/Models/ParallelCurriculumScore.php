<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumScore extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id','parallel_curriculum_id','parallel_curriculum_class_id',
        'student_id','parallel_curriculum_subject_id','assessment_template_component_id',
        'term_id','session_id','entered_by','score','entered_at',
    ];

    protected function casts(): array
    {
        return ['score' => 'float', 'entered_at' => 'datetime'];
    }

    public function curriculum(): BelongsTo { return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id'); }
    public function curriculumClass(): BelongsTo { return $this->belongsTo(ParallelCurriculumClass::class, 'parallel_curriculum_class_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function subject(): BelongsTo { return $this->belongsTo(ParallelCurriculumSubject::class, 'parallel_curriculum_subject_id'); }
    public function component(): BelongsTo { return $this->belongsTo(AssessmentTemplateComponent::class, 'assessment_template_component_id'); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function session(): BelongsTo { return $this->belongsTo(AcademicSession::class, 'session_id'); }
    public function enteredBy(): BelongsTo { return $this->belongsTo(User::class, 'entered_by'); }
}
