<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelCurriculumWorkingDay extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'parallel_curriculum_id',
        'day_of_week',
        'is_working',
        'resumption_time',
        'closing_time',
        'grace_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_working' => 'boolean',
            'grace_minutes' => 'integer',
        ];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id');
    }
}
