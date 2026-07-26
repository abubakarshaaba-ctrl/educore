<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelRoom extends BaseTenantModel
{
    protected $fillable = ['tenant_id', 'hostel_id', 'room_number', 'capacity'];

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(HostelAllocation::class, 'room_id');
    }

    public function occupiedCount(): int
    {
        return $this->allocations()->where('status', 'active')->count();
    }

    public function hasSpace(): bool
    {
        return $this->occupiedCount() < $this->capacity;
    }
}
