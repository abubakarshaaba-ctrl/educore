<?php

namespace App\Models;

use App\Enums\DataMigrationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataMigration extends Model
{
    protected $fillable = [
        'tenant_id', 'batch_number', 'direction', 'migration_type', 'source_system',
        'destination_system', 'status', 'initiated_by', 'approved_by', 'executed_by',
        'total_files', 'total_datasets', 'total_source_rows', 'total_valid_rows',
        'total_created', 'total_updated', 'total_skipped', 'total_failed', 'checksum',
        'started_at', 'completed_at', 'rolled_back_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => DataMigrationStatus::class,
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(MigrationFile::class, 'migration_id');
    }

    public function datasets(): HasMany
    {
        return $this->hasMany(MigrationDataset::class, 'migration_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(MigrationRow::class, 'migration_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(MigrationIssue::class, 'migration_id');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(MigrationCheckpoint::class, 'migration_id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(MigrationMapping::class, 'migration_id');
    }

    public function blueprintNodes(): HasMany
    {
        return $this->hasMany(MigrationBlueprintNode::class, 'migration_id');
    }

    public function coreRecords(): HasMany
    {
        return $this->hasMany(MigrationCoreRecord::class, 'migration_id');
    }

    public function historicalRecords(): HasMany
    {
        return $this->hasMany(MigrationHistoricalRecord::class, 'migration_id');
    }

    public function financialRecords(): HasMany
    {
        return $this->hasMany(MigrationFinancialRecord::class, 'migration_id');
    }
}
