<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'invoice_id', 'student_id', 'gateway_reference', 'gateway',
        'amount_paid', 'currency', 'status', 'gateway_response', 'split_breakdown',
        'paid_by_name', 'paid_by_phone', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid'      => 'float',
            'gateway_response' => 'array',
            'split_breakdown'  => 'array',
            'paid_at'          => 'datetime',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }

    public function isSuccessful(): bool { return $this->status === 'success'; }
}
