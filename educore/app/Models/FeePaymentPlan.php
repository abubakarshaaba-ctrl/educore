<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FeePaymentPlan extends BaseTenantModel
{
    protected $table = 'fee_payment_plans';
    protected $fillable = [
        'tenant_id','name','description','installments_count',
        'installment_schedule','is_active','is_default','surcharge_pct',
    ];
    protected $casts = [
        'installment_schedule' => 'array',
        'is_active'   => 'boolean',
        'is_default'  => 'boolean',
        'surcharge_pct' => 'decimal:2',
    ];

    public function invoicePlans() { return $this->hasMany(InvoicePaymentPlan::class, 'plan_id'); }
}
