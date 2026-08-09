<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationHistoricalDependency extends Model
{
    protected $fillable = ['migration_id', 'historical_record_id', 'relationship_field', 'parent_entity_type', 'parent_source_key', 'parent_source', 'resolved_record_id', 'resolution_status'];
}
