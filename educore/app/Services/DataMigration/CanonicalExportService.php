<?php

namespace App\Services\DataMigration;

use App\Models\DataMigration;
use App\Models\MigrationExportPackage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class CanonicalExportService
{
    public function __construct(private LifecycleAuditLogger $audit) {}

    public function export(Tenant $tenant, User $actor, array $entities = [], ?DataMigration $migration = null): MigrationExportPackage
    {
        $this->authorise($tenant, $actor);
        $registry = config('data_migration_export.entities');
        $type = $entities ? 'selective' : 'full';
        $entities = $entities ?: array_keys($registry);
        if ($unknown = array_diff($entities, array_keys($registry))) {
            throw new InvalidArgumentException('Unsupported export entities: '.implode(', ', $unknown));
        }

        $tmp = tempnam(sys_get_temp_dir(), 'educore-export-');
        $zipPath = $tmp.'.zip';
        rename($tmp, $zipPath);
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Export package cannot be created.');
        }
        $datasets = [];
        $temporaryDatasets = [];

        try {
            foreach ($entities as $entity) {
                $definition = $registry[$entity];
                $dataPath = tempnam(sys_get_temp_dir(), 'educore-jsonl-');
                $temporaryDatasets[] = $dataPath;
                $handle = fopen($dataPath, 'wb');
                $hash = hash_init('sha256');
                $count = 0;
                $query = DB::table($definition['table'])->where('tenant_id', $tenant->id)->orderBy('id');
                if (isset($definition['where']['role_not'])) {
                    $query->whereNotIn('role', $definition['where']['role_not']);
                }
                foreach ($query->cursor() as $record) {
                    $payload = $this->canonical((array) $record);
                    $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)."\n";
                    fwrite($handle, $line);
                    hash_update($hash, $line);
                    $count++;
                }
                fclose($handle);
                $name = "datasets/{$entity}.jsonl";
                $zip->addFile($dataPath, $name);
                $datasets[$entity] = ['path' => $name, 'records' => $count, 'sha256' => hash_final($hash), 'encoding' => 'jsonl'];
            }
            $manifest = ['format' => 'educore-portable-migration', 'format_version' => config('data_migration_export.format_version'), 'canonical_schema_version' => config('data_migration_schema.version'), 'generated_at' => now()->toIso8601String(), 'tenant' => ['source_tenant_id' => $tenant->id, 'slug' => $tenant->slug, 'name' => $tenant->name], 'export_type' => $type, 'datasets' => $datasets, 'relationships' => ['guardian_student', 'student_enrollments', 'subject_registrations'], 'integrity_algorithm' => 'sha256'];
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $zip->close();
            $sha = hash_file('sha256', $zipPath);
            $disk = config('data_migration_export.disk');
            $path = trim(config('data_migration_export.prefix'), '/').'/'.$tenant->id.'/'.Str::uuid().'/educore-export.zip';
            $stream = fopen($zipPath, 'rb');
            Storage::disk($disk)->put($path, $stream);
            fclose($stream);
            $package = MigrationExportPackage::create(['migration_id' => $migration?->id, 'tenant_id' => $tenant->id, 'created_by' => $actor->id, 'export_type' => $type, 'schema_version' => config('data_migration_schema.version'), 'package_format_version' => config('data_migration_export.format_version'), 'storage_disk' => $disk, 'storage_path' => $path, 'file_size' => filesize($zipPath), 'sha256' => $sha, 'manifest' => $manifest, 'scope' => $entities, 'status' => 'ready', 'verified_at' => now()]);
            $this->audit->record($tenant->id, $actor, $package, 'data_migration.export_created', [], ['type' => $type, 'sha256' => $sha, 'datasets' => array_map(fn ($dataset) => $dataset['records'], $datasets)]);

            return $package;
        } finally {
            foreach ($temporaryDatasets as $file) {
                @unlink($file);
            }
            @unlink($zipPath);
        }
    }

    public function verify(MigrationExportPackage $package): bool
    {
        $stream = Storage::disk($package->storage_disk)->readStream($package->storage_path);
        if (! $stream) {
            return false;
        }
        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        fclose($stream);

        return hash_equals($package->sha256, hash_final($context));
    }

    private function canonical(array $record): array
    {
        unset($record['tenant_id'], $record['password'], $record['remember_token'], $record['two_factor_secret'], $record['qr_secret'], $record['attendance_pin']);

        return ['educore_id' => $record['id'] ?? null] + $record;
    }

    private function authorise(Tenant $tenant, User $actor): void
    {
        if (! $actor->isSuperAdmin() && ((int) $actor->tenant_id !== (int) $tenant->id || ! $actor->isAdmin())) {
            throw new InvalidArgumentException('Cross-tenant export denied.');
        }
    }
}
