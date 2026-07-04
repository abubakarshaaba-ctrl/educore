<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceDiscountTemplate extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'name', 'type', 'value', 'is_active',
    ];

    protected function casts(): array
    {
        return ['value' => 'float', 'is_active' => 'boolean'];
    }

    public function computeDiscount(float $amount): float
    {
        if ($this->type === 'percentage') {
            return round($amount * ($this->value / 100), 2);
        }
        return min($this->value, $amount); // fixed, can't exceed invoice total
    }

    public function label(): string
    {
        return $this->type === 'percentage'
            ? "{$this->name} ({$this->value}%)"
            : "{$this->name} (₦" . number_format($this->value, 2) . ")";
    }
}
