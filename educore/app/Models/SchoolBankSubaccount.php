<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolBankSubaccount extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'purpose_name', 'gateway_subaccount_code',
        'bank_name', 'account_number', 'account_name', 'gateway', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function feeCategories(): HasMany { return $this->hasMany(FeeCategory::class); }
}
