<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffSalarySetting extends BaseTenantModel
{
    protected $table = 'staff_salary_settings';

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowances',
        'annual_rent_paid',
        'bank_name',
        'account_number',
        'account_name',
        'tax_identification_number',
        'bvn',
        'nin',
        'bank_details_locked',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bank_details_locked' => 'boolean',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
