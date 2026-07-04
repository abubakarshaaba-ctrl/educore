<?php
namespace App\Models;

class StaffDeduction extends BaseTenantModel
{
    protected $table = 'staff_deductions';

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'payroll_deduction_template_id',
        'custom_amount',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'custom_amount' => 'float',
            'is_active'     => 'boolean',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function template()
    {
        return $this->belongsTo(PayrollDeductionTemplate::class, 'payroll_deduction_template_id');
    }

    /**
     * The amount this deduction contributes for a given gross salary.
     * Uses the custom override if set, otherwise falls back to the template's
     * own calc_method (percentage of gross, or a fixed amount).
     */
    public function amountFor(float $grossSalary): float
    {
        if ($this->custom_amount !== null) {
            return (float) $this->custom_amount;
        }
        return $this->template ? $this->template->compute($grossSalary) : 0.0;
    }

    public function label(): string
    {
        $name = $this->template?->name ?? 'Deduction';
        return $this->notes ? "{$name} ({$this->notes})" : $name;
    }
}
