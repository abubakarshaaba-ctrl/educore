<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends BaseTenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'student_id', 'term_id', 'session_id',
        'invoice_number', 'total_amount', 'amount_paid', 'status', 'due_date',
        'generation_batch_id', 'discount_template_id', 'discount_amount', 'notes',
        'has_payment_plan', 'next_installment_due',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'float',
            'amount_paid'  => 'float',
            'due_date'     => 'date',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function session(): BelongsTo { return $this->belongsTo(AcademicSession::class, 'session_id'); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function transactions(): HasMany { return $this->hasMany(PaymentTransaction::class); }
    public function discounts(): HasMany { return $this->hasMany(InvoiceDiscount::class); }

    public function getBalanceAttribute(): float
    {
        return $this->total_amount - $this->amount_paid;
    }

    public function isPaid(): bool { return $this->status === 'paid'; }
    public function isUnpaid(): bool { return $this->status === 'unpaid'; }
    public function isPartial(): bool { return $this->status === 'partially_paid'; }
}
