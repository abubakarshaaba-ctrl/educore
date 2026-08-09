<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationEntityLink extends Model
{
    protected $fillable = [
        'tenant_id', 'migration_id', 'source_system', 'entity_type', 'source_table',
        'source_record_id', 'educore_record_id', 'destination_record_id',
        'source_business_identifier', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }
}
