<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffStatusHistory extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'old_status',
        'new_status',
        'effective_date',
        'last_working_date',
        'reason',
        'document_path',
        'changed_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'last_working_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->staff();
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
