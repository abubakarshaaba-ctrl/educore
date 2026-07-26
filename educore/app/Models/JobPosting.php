<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'title', 'department', 'description', 'requirements', 'status', 'posted_by', 'closes_at',
    ];

    protected function casts(): array
    {
        return ['closes_at' => 'date'];
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(JobApplicant::class);
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
