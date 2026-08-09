<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationMappingProfile extends Model
{
    protected $fillable = ['tenant_id', 'name', 'source_system', 'canonical_entity', 'schema_version', 'mappings', 'transformations', 'created_by', 'updated_by', 'is_active', 'usage_count', 'last_used_at'];

    protected function casts(): array
    {
        return ['mappings' => 'array', 'transformations' => 'array', 'is_active' => 'boolean', 'last_used_at' => 'datetime'];
    }
}
