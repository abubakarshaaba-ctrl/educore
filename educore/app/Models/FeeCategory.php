<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeCategory extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'school_bank_subaccount_id', 'name', 'is_mandatory',
    ];

    protected function casts(): array
    {
        return ['is_mandatory' => 'boolean'];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function subaccount(): BelongsTo { return $this->belongsTo(SchoolBankSubaccount::class, 'school_bank_subaccount_id'); }
    public function feeStructures(): HasMany { return $this->hasMany(FeeStructure::class); }
    public function invoiceItems(): HasMany { return $this->hasMany(InvoiceItem::class); }
}
