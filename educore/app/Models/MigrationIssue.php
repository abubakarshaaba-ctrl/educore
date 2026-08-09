<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationIssue extends Model
{
    protected $fillable = [
        'migration_id', 'dataset_id', 'migration_row_id', 'migration_file_id', 'severity',
        'category', 'field', 'source_value', 'message', 'suggested_resolution', 'status',
        'resolved_by', 'resolved_at', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'resolved_at' => 'datetime'];
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }
}
