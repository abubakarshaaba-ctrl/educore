<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationApproval extends Model
{
    protected $fillable = ['migration_id', 'tenant_id', 'approval_type', 'decision', 'decided_by', 'reason', 'approved_snapshot', 'decided_at'];

    protected function casts(): array
    {
        return ['approved_snapshot' => 'array', 'decided_at' => 'datetime'];
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }
}
