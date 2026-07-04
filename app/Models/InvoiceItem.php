<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'invoice_id', 'fee_category_id', 'description', 'amount',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function feeCategory(): BelongsTo { return $this->belongsTo(FeeCategory::class); }
}
