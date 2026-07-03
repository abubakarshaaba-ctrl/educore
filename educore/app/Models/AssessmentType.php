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
        'is_exam',
    ];

    protected function casts(): array
    {
        return [
            'weight_percentage' => 'float',
            'is_exam'           => 'boolean',
        ];
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
