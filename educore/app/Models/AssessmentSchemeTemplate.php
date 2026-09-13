<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentSchemeTemplate extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssessmentSchemeTemplateItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
