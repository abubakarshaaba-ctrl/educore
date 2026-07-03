<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'student_id', 'guardian_id', 'channel', 'recipient',
        'subject', 'message', 'status', 'gateway_message_id', 'gateway_response',
        'unit_cost', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'gateway_response' => 'array',
            'unit_cost'        => 'float',
            'sent_at'          => 'datetime',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function guardian(): BelongsTo { return $this->belongsTo(Guardian::class); }

    public function isSent(): bool { return in_array($this->status, ['sent', 'delivered']); }
}
