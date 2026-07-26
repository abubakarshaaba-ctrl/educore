<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Named SchoolAsset (not Asset) to avoid clashing with common framework/asset() helpers. */
class SchoolAsset extends BaseTenantModel
{
    protected $table = 'assets';

    protected $fillable = [
        'tenant_id', 'name', 'category', 'serial_number', 'location', 'assigned_to',
        'purchase_date', 'purchase_cost', 'condition', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
        ];
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
