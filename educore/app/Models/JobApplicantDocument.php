<?php

namespace App\Models;

class JobApplicantDocument extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'job_applicant_id', 'document_type', 'file_path', 'original_name',
        'verification_status', 'verification_note', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function applicant()
    {
        return $this->belongsTo(JobApplicant::class, 'job_applicant_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool { return $this->verification_status === 'verified'; }
    public function isRejected(): bool { return $this->verification_status === 'rejected'; }
    public function isPending(): bool  { return $this->verification_status === 'pending'; }
}
