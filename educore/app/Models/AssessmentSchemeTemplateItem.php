<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentSchemeTemplateItem extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'assessment_scheme_template_id',
        'name',
        'weight_percentage',
        'objective_max',
        'theory_max',
        'is_exam',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weight_percentage' => 'float',
            'objective_max' => 'float',
            'theory_max' => 'float',
            'is_exam' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssessmentSchemeTemplate::class, 'assessment_scheme_template_id');
    }
}
