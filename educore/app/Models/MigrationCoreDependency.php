<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationCoreDependency extends Model
{
    protected $fillable = ['migration_id', 'core_record_id', 'relationship_field', 'parent_entity_type', 'parent_source_key', 'parent_source', 'resolved_record_id', 'resolution_status', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(MigrationCoreRecord::class, 'core_record_id');
    }
}
