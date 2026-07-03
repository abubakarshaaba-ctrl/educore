<?php
namespace App\Models;
class PayrollRoleTemplate extends BaseTenantModel
{
    protected $fillable = ['tenant_id','role','label','basic_salary','housing_allowance',
                           'transport_allowance','other_allowances','deduction_ids','is_active'];
    protected function casts(): array {
        return ['basic_salary'=>'float','housing_allowance'=>'float',
                'transport_allowance'=>'float','other_allowances'=>'float',
                'deduction_ids'=>'array','is_active'=>'boolean'];
    }
    public function grossSalary(): float {
        return $this->basic_salary + $this->housing_allowance
             + $this->transport_allowance + $this->other_allowances;
    }
}
