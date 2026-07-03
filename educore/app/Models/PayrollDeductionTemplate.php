<?php
namespace App\Models;
class PayrollDeductionTemplate extends BaseTenantModel
{
    protected $fillable = ['tenant_id','name','type','calc_method','value','is_active','description'];
    protected function casts(): array { return ['value'=>'float','is_active'=>'boolean']; }
    public function compute(float $grossSalary): float {
        return $this->calc_method === 'percentage'
            ? round($grossSalary * ($this->value / 100), 2)
            : $this->value;
    }
    public function label(): string {
        return $this->name . ' (' . ($this->calc_method==='percentage' ? $this->value.'%' : '₦'.number_format($this->value,2)) . ')';
    }
}
