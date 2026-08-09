<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MigrationBlueprintNode extends Model
{
    protected $fillable = ['migration_id', 'dataset_id', 'tenant_id', 'entity_type', 'source_key', 'canonical_payload', 'decision', 'confidence', 'matched_record_id', 'payload_checksum', 'match_candidates', 'metadata', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return ['canonical_payload' => 'array', 'match_candidates' => 'array', 'metadata' => 'array', 'confidence' => 'decimal:2', 'reviewed_at' => 'datetime'];
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(MigrationBlueprintDependency::class, 'node_id');
    }
}
