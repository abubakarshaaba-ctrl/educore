<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicStudentClassLink extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'class_arm_id', 'session_id', 'term_id',
        'token_hash', 'expires_at', 'revoked_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function classArm(): BelongsTo { return $this->belongsTo(ClassArm::class); }
    public function session(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function isUsable(): bool
    {
        return !$this->revoked_at && $this->expires_at->isFuture();
    }
}
