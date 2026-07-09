<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobInterview extends BaseTenantModel
{
    protected $fillable = ['tenant_id', 'applicant_id', 'interview_at', 'interviewer_id', 'notes', 'outcome'];

    protected function casts(): array
    {
        return ['interview_at' => 'datetime'];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(JobApplicant::class, 'applicant_id');
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }
}
