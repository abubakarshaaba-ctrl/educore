<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionDocument extends BaseTenantModel
{
    protected $table = 'admission_documents';

    protected $fillable = [
        'admission_id', 'tenant_id', 'document_type',
        'file_path', 'original_name',
        'verification_status', 'verification_note', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function admission() { return $this->belongsTo(Admission::class); }
    public function verifiedBy() { return $this->belongsTo(\App\Models\User::class, 'verified_by'); }

    public function isVerified(): bool { return $this->verification_status === 'verified'; }
    public function isRejected(): bool { return $this->verification_status === 'rejected'; }
    public function isPending(): bool  { return $this->verification_status === 'pending'; }
}
