<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateIssuance extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'student_id', 'certificate_type', 'serial_number', 'issued_by', 'issued_at',
    ];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
