<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplicantMessage extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'job_applicant_id', 'sender_type', 'sender_user_id', 'body', 'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(JobApplicant::class, 'job_applicant_id');
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
