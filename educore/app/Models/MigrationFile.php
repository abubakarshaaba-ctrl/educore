<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class MigrationFile extends Model
{
    private const IMMUTABLE_SOURCE_FIELDS = [
        'tenant_id', 'migration_id', 'original_filename', 'sanitized_filename', 'mime_type',
        'detected_file_type', 'file_size', 'sha256', 'storage_disk', 'storage_path',
        'parser_version', 'is_original', 'uploaded_by', 'uploaded_at',
    ];

    protected $fillable = [...self::IMMUTABLE_SOURCE_FIELDS, 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'is_original' => 'boolean', 'uploaded_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $file): void {
            foreach (self::IMMUTABLE_SOURCE_FIELDS as $field) {
                if ($file->isDirty($field)) {
                    throw new LogicException("Immutable migration source metadata cannot be changed: {$field}");
                }
            }
        });
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class, 'migration_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
