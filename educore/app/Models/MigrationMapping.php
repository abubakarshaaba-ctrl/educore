<?php

namespace App\Models;

use App\Enums\MigrationMappingDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationMapping extends Model
{
    protected $fillable = [
        'migration_id', 'dataset_id', 'source_column', 'destination_entity',
        'destination_field', 'decision', 'confidence', 'transformation_rule',
        'metadata', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['decision' => MigrationMappingDecision::class, 'transformation_rule' => 'array', 'metadata' => 'array', 'confidence' => 'decimal:2', 'reviewed_at' => 'datetime'];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(MigrationDataset::class, 'dataset_id');
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }
}
