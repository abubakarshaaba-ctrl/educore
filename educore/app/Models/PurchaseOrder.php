<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'vendor_id', 'po_number', 'status', 'total_amount', 'notes',
        'requested_by', 'approved_by', 'approved_at', 'expected_delivery_date',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'expected_delivery_date' => 'date',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
