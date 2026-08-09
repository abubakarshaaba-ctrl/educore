<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationCheckpoint extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'migration_id', 'dataset_id', 'stage', 'last_row_number', 'processed_rows',
        'checkpoint_checksum', 'state', 'created_at',
    ];

    protected function casts(): array
    {
        return ['state' => 'array', 'created_at' => 'datetime'];
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }
}
