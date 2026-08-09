<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MigrationCoreRecord extends Model
{
    protected $fillable = ['migration_id', 'dataset_id', 'migration_row_id', 'tenant_id', 'entity_type', 'source_key', 'canonical_payload', 'decision', 'matched_record_id', 'payload_checksum', 'match_candidates', 'metadata'];

    protected function casts(): array
    {
        return ['canonical_payload' => 'array', 'match_candidates' => 'array', 'metadata' => 'array'];
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(MigrationCoreDependency::class, 'core_record_id');
    }
}
