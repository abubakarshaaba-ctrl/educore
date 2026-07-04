<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FeeInstallment extends BaseTenantModel
{
    protected $table = 'fee_installments';
    protected $fillable = [
        'tenant_id','invoice_id','invoice_payment_plan_id',
        'installment_number','amount_due','amount_paid',
        'due_date','paid_date','status','reminder_sent',
    ];
    protected $casts = [
        'due_date'  => 'date',
        'paid_date' => 'date',
        'reminder_sent' => 'boolean',
    ];

    public function invoice()      { return $this->belongsTo(Invoice::class); }
    public function invoicePlan()  { return $this->belongsTo(InvoicePaymentPlan::class, 'invoice_payment_plan_id'); }

    public function getBalanceAttribute(): float
    {
        return max(0, $this->amount_due - $this->amount_paid);
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date->isPast();
    }

    public function scopeOverdue($q)
    {
        return $q->where('status', '!=', 'paid')->where('due_date', '<', now()->toDateString());
    }

    public function scopeDueSoon($q, int $days = 7)
    {
        return $q->where('status', '!=', 'paid')
                 ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }
}
