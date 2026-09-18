<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParallelCurriculumIntegration extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id','parallel_curriculum_id','destination_class_level_id',
        'destination_subject_id','calculation_method','require_all_subjects',
        'minimum_completed_subjects','auto_sync','is_active',
    ];

    protected function casts(): array
    {
        return [
            'require_all_subjects' => 'boolean',
            'minimum_completed_subjects' => 'integer',
            'auto_sync' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function curriculum(): BelongsTo { return $this->belongsTo(ParallelCurriculum::class, 'parallel_curriculum_id'); }
    public function destinationClassLevel(): BelongsTo { return $this->belongsTo(ClassLevel::class, 'destination_class_level_id'); }
    public function destinationSubject(): BelongsTo { return $this->belongsTo(Subject::class, 'destination_subject_id'); }
    public function composites(): HasMany { return $this->hasMany(ParallelCurriculumComposite::class, 'parallel_curriculum_integration_id'); }
}
