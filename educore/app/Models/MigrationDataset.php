<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MigrationDataset extends Model
{
    protected $fillable = [
        'migration_id', 'migration_file_id', 'source_name', 'canonical_entity',
        'classification_status', 'classification_confidence', 'source_row_count',
        'staged_row_count', 'source_schema', 'metadata',
    ];

    protected function casts(): array
    {
        return ['source_schema' => 'array', 'metadata' => 'array', 'classification_confidence' => 'decimal:2'];
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(MigrationFile::class, 'migration_file_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(MigrationRow::class, 'dataset_id');
    }
}
