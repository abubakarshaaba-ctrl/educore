<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AscReturn extends BaseTenantModel
{
    protected $table = 'asc_returns';

    protected $fillable = [
        'tenant_id',
        'session_id',
        'census_year',
        'reference_date',
        'status',
        'auto_data',
        'manual_data',
        'completeness',
        'reconciliation',
        'synchronized_at',
        'finalized_at',
        'finalized_by',
        'submitted_at',
        'submitted_by',
    ];

    protected $casts = [
        'reference_date' => 'date',
        'auto_data' => 'array',
        'manual_data' => 'array',
        'completeness' => 'array',
        'reconciliation' => 'array',
        'synchronized_at' => 'datetime',
        'finalized_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['finalized', 'submitted', 'archived'], true);
    }
}
