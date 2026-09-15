<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentTemplate extends BaseTenantModel
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DRAFT = 'draft';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'status',
    ];

    public function components(): HasMany
    {
        return $this->hasMany(AssessmentTemplateComponent::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssessmentTemplateAssignment::class);
    }

    public function getTotalWeightAttribute(): float
    {
        if ($this->relationLoaded('components')) {
            return round((float) $this->components->sum('weight_percentage'), 2);
        }

        return round((float) $this->components()->sum('weight_percentage'), 2);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * A template remains editable/deletable until a score has actually been
     * recorded against one of the runtime assessment types materialized from
     * its assignments. Assignment alone must not permanently lock a template.
     */
    public function hasRecordedScores(): bool
    {
        $assignments = $this->relationLoaded('assignments')
            ? $this->assignments
            : $this->assignments()->get();

        foreach ($assignments as $assignment) {
            $termIds = Term::withoutTenantScope()
                ->where('tenant_id', $this->tenant_id)
                ->where('session_id', $assignment->session_id)
                ->pluck('id');

            if ($termIds->isEmpty()) {
                continue;
            }

            $assessmentTypeIds = AssessmentType::withoutTenantScope()
                ->where('tenant_id', $this->tenant_id)
                ->whereIn('term_id', $termIds)
                ->whereHas('classLevels', fn ($query) => $query
                    ->where('class_levels.id', $assignment->class_level_id))
                ->pluck('assessment_types.id');

            if ($assessmentTypeIds->isNotEmpty()
                && Score::withoutTenantScope()->whereIn('assessment_type_id', $assessmentTypeIds)->exists()) {
                return true;
            }
        }

        return false;
    }
}
