<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructure extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'fee_category_id', 'class_level_id', 'term_id', 'amount', 'is_active',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float', 'is_active' => 'boolean'];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function feeCategory(): BelongsTo { return $this->belongsTo(FeeCategory::class); }
    public function classLevel(): BelongsTo { return $this->belongsTo(ClassLevel::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
}
