<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationBlueprintDependency extends Model
{
    protected $fillable = ['migration_id', 'node_id', 'relationship_field', 'parent_entity_type', 'parent_source_key', 'parent_node_id', 'resolution_status', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(MigrationBlueprintNode::class, 'node_id');
    }

    public function parentNode(): BelongsTo
    {
        return $this->belongsTo(MigrationBlueprintNode::class, 'parent_node_id');
    }
}
