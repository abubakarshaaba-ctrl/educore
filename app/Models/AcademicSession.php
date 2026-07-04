<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSession extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class, 'session_id');
    }

    public function currentTerm()
    {
        return $this->terms()->where('is_current', true)->first();
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
