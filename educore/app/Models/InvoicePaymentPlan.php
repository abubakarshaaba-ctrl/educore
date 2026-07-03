<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class InvoicePaymentPlan extends BaseTenantModel
{
    protected $table = 'invoice_payment_plans';
    protected $fillable = ['tenant_id','invoice_id','plan_id'];

    public function invoice()      { return $this->belongsTo(Invoice::class); }
    public function plan()         { return $this->belongsTo(FeePaymentPlan::class, 'plan_id'); }
    public function installments() { return $this->hasMany(FeeInstallment::class, 'invoice_payment_plan_id'); }
}
