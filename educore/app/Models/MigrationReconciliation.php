<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationReconciliation extends Model
{
    protected $fillable = [
        'migration_id', 'scope', 'source_count', 'destination_count', 'source_total',
        'destination_total', 'status', 'details', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['details' => 'array', 'source_total' => 'decimal:4', 'destination_total' => 'decimal:4', 'verified_at' => 'datetime'];
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }
}
