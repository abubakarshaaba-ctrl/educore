<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlanInstallment extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id',
        'invoice_payment_plan_id',
        'installment_number',
        'due_date',
        'amount_due',
        'amount_paid',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount_due' => 'float',
            'amount_paid' => 'float',
        ];
    }

    public function invoicePaymentPlan(): BelongsTo
    {
        return $this->belongsTo(InvoicePaymentPlan::class);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->amount_due - (float) $this->amount_paid);
    }
}
