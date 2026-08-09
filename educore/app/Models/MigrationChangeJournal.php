<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationChangeJournal extends Model
{
    protected $fillable = ['migration_id', 'tenant_id', 'entity_type', 'entity_id', 'classification', 'operation', 'sequence', 'before_image', 'after_image', 'before_checksum', 'after_checksum', 'rollback_status', 'compensation_strategy', 'rollback_error', 'rolled_back_at'];

    protected function casts(): array
    {
        return ['before_image' => 'array', 'after_image' => 'array', 'rolled_back_at' => 'datetime'];
    }
}
