<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumPromotionRule extends BaseTenantModel
{
    public const FAILURE_REPEAT = 'repeat';
    public const FAILURE_RETAIN = 'retain';

    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_id',
        'source_class_id',
        'destination_class_id',
        'minimum_average',
        'max_failed_subjects',
        'require_complete_result',
        'failure_action',
        'arm_strategy',
        'is_terminal',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minimum_average' => 'float',
            'max_failed_subjects' => 'integer',
            'require_complete_result' => 'boolean',
            'is_terminal' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id');
    }

    public function sourceClass(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculumClass::class, 'source_class_id');
    }

    public function destinationClass(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculumClass::class, 'destination_class_id');
    }
}
