<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumGrade extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_id',
        'grade_letter',
        'min_score',
        'max_score',
        'remark',
        'is_pass_grade',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_score' => 'float',
            'max_score' => 'float',
            'is_pass_grade' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id');
    }
}
