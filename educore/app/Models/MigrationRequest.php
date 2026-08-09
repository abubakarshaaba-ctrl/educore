<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationRequest extends Model
{
    protected $fillable = [
        'migration_id', 'tenant_id', 'requested_by', 'status', 'requested_scope',
        'business_justification', 'data_scope', 'risk_level', 'school_approved_by',
        'school_approved_at', 'platform_approved_by', 'platform_approved_at', 'decision_reason',
    ];

    protected function casts(): array
    {
        return ['data_scope' => 'array', 'school_approved_at' => 'datetime', 'platform_approved_at' => 'datetime'];
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }
}
