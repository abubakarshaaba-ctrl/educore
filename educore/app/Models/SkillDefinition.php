<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillDefinition extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'category',
        'name',
        'order_index',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(StudentSkillRating::class);
    }

    public function scopePsychomotor($query)
    {
        return $query->where('category', 'psychomotor')->orderBy('order_index');
    }

    public function scopeAffective($query)
    {
        return $query->where('category', 'affective')->orderBy('order_index');
    }
}
