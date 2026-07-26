<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'name', 'contact_person', 'phone', 'email', 'address', 'category', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
