<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends BaseTenantModel
{
    protected $table = 'payroll_periods';

    protected $fillable = [
        'tenant_id',
        'title',
        'period_start',
        'period_end',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'approved_by',
        'payment_date',
    ];
}
