<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hostel extends BaseTenantModel
{
    protected $fillable = ['tenant_id', 'name', 'gender', 'capacity', 'warden_id'];

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(HostelAllocation::class);
    }

    public function warden(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warden_id');
    }

    public function occupiedCount(): int
    {
        return $this->allocations()->where('status', 'active')->count();
    }
}
