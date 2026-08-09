<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationFinancialRecord extends Model
{
    protected $fillable = ['migration_id', 'dataset_id', 'migration_row_id', 'tenant_id', 'entity_type', 'source_key', 'canonical_payload', 'decision', 'matched_record_id', 'payload_checksum', 'metadata'];

    protected function casts(): array
    {
        return ['canonical_payload' => 'array', 'metadata' => 'array'];
    }
}
