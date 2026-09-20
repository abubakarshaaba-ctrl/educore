<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumSkillRating extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_enrolment_id',
        'parallel_curriculum_id',
        'parallel_curriculum_class_id',
        'parallel_curriculum_class_arm_id',
        'student_id',
        'skill_definition_id',
        'term_id',
        'session_id',
        'rating',
        'rated_by',
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(
            ParallelCurriculumEnrolment::class,
            'parallel_curriculum_enrolment_id'
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(SkillDefinition::class, 'skill_definition_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function ratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_by');
    }

    public function getRatingLabelAttribute(): string
    {
        return match ((int) $this->rating) {
            5 => 'Excellent',
            4 => 'Very Good',
            3 => 'Good',
            2 => 'Fair',
            1 => 'Poor',
            default => '—',
        };
    }
}
