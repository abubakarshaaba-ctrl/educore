<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplicant extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'job_posting_id', 'name', 'email', 'phone', 'resume_path',
        'cover_letter', 'status', 'notes', 'applied_at',
    ];

    protected function casts(): array
    {
        return ['applied_at' => 'datetime'];
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(JobInterview::class, 'applicant_id');
    }
}
