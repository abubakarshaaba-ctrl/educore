<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumResultComment extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_enrolment_id',
        'term_id',
        'form_teacher_id',
        'form_teacher_comment',
    ];

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(
            ParallelCurriculumEnrolment::class,
            'parallel_curriculum_enrolment_id'
        );
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function formTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'form_teacher_id');
    }
}
