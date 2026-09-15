<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentTemplateComponent extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'assessment_template_id',
        'name',
        'weight_percentage',
        'component_type',
        'entry_mode',
        'objective_max',
        'theory_max',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weight_percentage' => 'float',
            'objective_max' => 'float',
            'theory_max' => 'float',
            'sort_order' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssessmentTemplate::class, 'assessment_template_id');
    }

    public function isExam(): bool
    {
        return in_array($this->component_type, ['exam', 'objective_exam', 'theory_exam', 'final_exam'], true);
    }
}
