<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationRow extends Model
{
    protected $fillable = [
        'migration_id', 'dataset_id', 'row_number', 'source_identifier', 'raw_payload',
        'mapped_payload', 'normalised_payload', 'validation_status', 'mapping_confidence',
        'warnings', 'errors', 'resolved_entity_ids', 'source_record_checksum',
        'destination_record_checksum',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array', 'mapped_payload' => 'array', 'normalised_payload' => 'array',
            'warnings' => 'array', 'errors' => 'array', 'resolved_entity_ids' => 'array',
            'mapping_confidence' => 'decimal:2',
        ];
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(MigrationDataset::class, 'dataset_id');
    }
}
