<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationExportPackage extends Model
{
    protected $fillable = ['migration_id', 'tenant_id', 'created_by', 'export_type', 'schema_version', 'package_format_version', 'storage_disk', 'storage_path', 'file_size', 'sha256', 'manifest', 'scope', 'status', 'verified_at'];

    protected function casts(): array
    {
        return ['manifest' => 'array', 'scope' => 'array', 'verified_at' => 'datetime'];
    }
}
