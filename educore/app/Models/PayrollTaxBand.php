<?php
namespace App\Models;

class PayrollTaxBand extends BaseTenantModel
{
    protected $table = 'payroll_tax_bands';

    protected $fillable = [
        'tenant_id',
        'lower_bound',
        'upper_bound',
        'rate_percent',
        'order_index',
    ];

    protected function casts(): array
    {
        return [
            'lower_bound'  => 'float',
            'upper_bound'  => 'float',
            'rate_percent' => 'float',
        ];
    }
}
