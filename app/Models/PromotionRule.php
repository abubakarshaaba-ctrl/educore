<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionRule extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'class_level_id',
        'min_required_average',
        'max_failed_subjects_allowed',
        'compulsory_subject_ids',
    ];

    protected function casts(): array
    {
        return ['compulsory_subject_ids' => 'array'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }
}
