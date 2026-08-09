<?php

namespace App\Services\DataMigration;

use App\Models\DataMigration;
use App\Models\MigrationFile;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImmutableSourceStorage
{
    public function __construct(private readonly LifecycleAuditLogger $audit) {}

    public function preserve(DataMigration $migration, UploadedFile $upload, User $actor): MigrationFile
    {
        if (! $actor->isSuperAdmin() && (int) $actor->tenant_id !== (int) $migration->tenant_id) {
            throw new RuntimeException('Cross-tenant migration source upload denied.');
        }
        if (! $upload->isValid()) {
            throw new RuntimeException('The migration source upload is invalid.');
        }

        $size = $upload->getSize();
        if ($size === false || $size > config('data_migration.maximum_source_bytes')) {
            throw new RuntimeException('The migration source exceeds the configured size limit.');
        }

        $realPath = $upload->getRealPath();
        if (! is_string($realPath) || ! is_readable($realPath)) {
            throw new RuntimeException('The migration source cannot be read.');
        }

        $checksum = hash_file('sha256', $realPath);
        $original = $upload->getClientOriginalName();
        $extension = Str::lower($upload->getClientOriginalExtension());
        $sanitized = Str::slug(pathinfo($original, PATHINFO_FILENAME)) ?: 'source';
        $sanitized .= $extension !== '' ? ".{$extension}" : '';
        $disk = config('data_migration.source_disk');
        $path = sprintf('%s/%d/%s/%s/%s', trim(config('data_migration.source_prefix'), '/'), $migration->tenant_id, $migration->batch_number, Str::uuid(), $sanitized);

        return DB::transaction(function () use ($migration, $upload, $actor, $realPath, $checksum, $original, $sanitized, $extension, $size, $disk, $path): MigrationFile {
            $stream = fopen($realPath, 'rb');
            if ($stream === false || ! Storage::disk($disk)->put($path, $stream)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                throw new RuntimeException('The immutable migration source could not be stored.');
            }
            if (is_resource($stream)) {
                fclose($stream);
            }

            try {
                $storedHash = $this->hashStoredFile($disk, $path);
                if (! hash_equals($checksum, $storedHash)) {
                    throw new RuntimeException('Stored migration source checksum verification failed.');
                }

                $file = MigrationFile::create([
                    'migration_id' => $migration->id,
                    'tenant_id' => $migration->tenant_id,
                    'uploaded_by' => $actor->id,
                    'original_filename' => $original,
                    'sanitized_filename' => $sanitized,
                    'mime_type' => $upload->getMimeType(),
                    'detected_file_type' => $extension ?: null,
                    'file_size' => $size,
                    'sha256' => $checksum,
                    'storage_disk' => $disk,
                    'storage_path' => $path,
                    'parser_version' => config('data_migration.parser_version'),
                    'is_original' => true,
                    'uploaded_at' => now(),
                    'metadata' => ['client_mime_type' => $upload->getClientMimeType()],
                ]);

                $migration->increment('total_files');
                $this->audit->record($migration->tenant_id, $actor, $migration, 'data_migration.source_preserved', [], [
                    'migration_file_id' => $file->id,
                    'sha256' => $checksum,
                    'file_size' => $size,
                ]);

                return $file;
            } catch (\Throwable $exception) {
                Storage::disk($disk)->delete($path);
                throw $exception;
            }
        });
    }

    public function verify(MigrationFile $file): bool
    {
        $disk = Storage::disk($file->storage_disk);

        return $disk->exists($file->storage_path)
            && hash_equals($file->sha256, $this->hashStoredFile($file->storage_disk, $file->storage_path));
    }

    private function hashStoredFile(string $disk, string $path): string
    {
        $stream = Storage::disk($disk)->readStream($path);
        if ($stream === false) {
            throw new RuntimeException('The stored migration source cannot be read.');
        }

        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        fclose($stream);

        return hash_final($context);
    }
}
