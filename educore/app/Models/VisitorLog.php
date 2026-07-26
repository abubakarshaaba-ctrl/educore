<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorLog extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'visitor_name', 'phone', 'purpose', 'host_name', 'badge_number',
        'check_in_at', 'check_out_at', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isCheckedIn(): bool
    {
        return $this->check_out_at === null;
    }
}
