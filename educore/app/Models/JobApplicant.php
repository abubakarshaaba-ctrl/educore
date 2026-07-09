<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplicant extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'job_posting_id', 'access_token', 'name', 'email', 'phone', 'resume_path',
        'cover_letter', 'status', 'notes', 'applied_at', 'offer_letter_sent', 'offer_sent_at',
    ];

    protected function casts(): array
    {
        return ['applied_at' => 'datetime', 'offer_sent_at' => 'datetime', 'offer_letter_sent' => 'boolean'];
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(JobInterview::class, 'applicant_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(JobApplicantMessage::class)->orderBy('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(JobApplicantDocument::class);
    }
}
