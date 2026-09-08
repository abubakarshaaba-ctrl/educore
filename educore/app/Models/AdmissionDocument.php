<?php

namespace App\Models;

use App\Services\SensitiveDocumentStorage;
use Illuminate\Support\Facades\Log;

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

    protected static function booted(): void
    {
        static::created(function (self $document): void {
            try {
                $result = app(SensitiveDocumentStorage::class)->migrateToPrivate($document->file_path);
                if (in_array($result, ['failed', 'invalid', 'missing'], true)) {
                    Log::warning('Admission document could not be secured in private storage.', [
                        'document_id' => $document->id,
                        'tenant_id' => $document->tenant_id,
                        'result' => $result,
                    ]);
                }
            } catch (\Throwable $exception) {
                // The web-server deny rules still prevent direct access to the
                // legacy public directory; log the migration failure for repair.
                Log::error('Admission document private-storage migration failed.', [
                    'document_id' => $document->id,
                    'tenant_id' => $document->tenant_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }

    public function admission() { return $this->belongsTo(Admission::class); }
    public function verifiedBy() { return $this->belongsTo(\App\Models\User::class, 'verified_by'); }

    public function isVerified(): bool { return $this->verification_status === 'verified'; }
    public function isRejected(): bool { return $this->verification_status === 'rejected'; }
    public function isPending(): bool  { return $this->verification_status === 'pending'; }
}
