<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentType extends BaseTenantModel
{
    protected $table = 'assessment_types';

    protected $fillable = [
        'tenant_id',
        'term_id',
        'name',
        'weight_percentage',
        'objective_max',
        'theory_max',
        'is_exam',
    ];

    protected function casts(): array
    {
        return [
            'weight_percentage' => 'float',
            'objective_max'     => 'float',
            'theory_max'        => 'float',
            'is_exam'           => 'boolean',
        ];
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Split-scored assessment types pull an objective score from a tagged
     * CBT exam (read-only) and combine it with a manually-entered theory
     * score. Plain assessment types take one manually-entered value.
     */
    public function isSplit(): bool
    {
        return $this->objective_max !== null && $this->theory_max !== null;
    }
}
