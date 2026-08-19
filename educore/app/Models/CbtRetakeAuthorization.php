<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtRetakeAuthorization extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'cbt_exam_id', 'student_id', 'authorized_by', 'attempt_number',
        'reason', 'authorized_at', 'used_at', 'revoked_at', 'revoked_by', 'revocation_reason',
    ];

    protected function casts(): array
    {
        return ['authorized_at' => 'datetime', 'used_at' => 'datetime', 'revoked_at' => 'datetime', 'attempt_number' => 'integer'];
    }

    public function exam(): BelongsTo { return $this->belongsTo(CbtExam::class, 'cbt_exam_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function authorizer(): BelongsTo { return $this->belongsTo(User::class, 'authorized_by'); }
    public function revoker(): BelongsTo { return $this->belongsTo(User::class, 'revoked_by'); }
    public function available(): bool { return ! $this->used_at && ! $this->revoked_at; }
}
