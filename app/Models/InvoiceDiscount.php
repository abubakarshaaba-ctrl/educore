<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceDiscount extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'invoice_id', 'approved_by', 'reason', 'amount',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
