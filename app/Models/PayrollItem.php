<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollItem extends BaseTenantModel
{
    protected $table = 'payroll_items';

    protected function casts(): array
    {
        return [
            'deduction_breakdown' => 'array',
        ];
    }

    protected $fillable = [
        'tenant_id',
        'payroll_period_id',
        'staff_id',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowances',
        'gross_pay',
        'tax_deduction',
        'pension_deduction',
        'other_deductions',
        'deduction_breakdown',
        'total_deductions',
        'net_pay',
        'bank_name',
        'account_number',
        'account_name',
        'payment_status',
        'notes',
    ];

    public function staff() { return $this->belongsTo(\App\Models\User::class, 'staff_id'); }
    public function period() { return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id'); }
}
