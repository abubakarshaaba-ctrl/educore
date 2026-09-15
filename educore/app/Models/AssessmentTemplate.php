<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentTemplate extends BaseTenantModel
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DRAFT = 'draft';

    protected $fillable = [
        'tenant_id', 'name', 'description', 'status',
    ];

    public function components(): HasMany
    {
        return $this->hasMany(AssessmentTemplateComponent::class)->orderBy('sort_order')->orderBy('id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssessmentTemplateAssignment::class);
    }

    public function getTotalWeightAttribute(): float
    {
        if ($this->relationLoaded('components')) {
            return (float) $this->components->sum('weight_percentage');
        }

        return (float) $this->components()->sum('weight_percentage');
    }
}
