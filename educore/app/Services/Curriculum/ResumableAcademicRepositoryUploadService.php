<?php

namespace App\Services\Curriculum;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ResumableAcademicRepositoryUploadService
{
    private const ROOT = 'academic-repository/resumable-uploads';

    private const ALLOWED_EXTENSIONS = ['zip', 'docx', 'doc', 'pdf', 'xlsx', 'xls'];

    public function create(array $data, array $metadata, int $actor): array
    {
        $this->purgeExpiredSessions();

        $filename = basename(str_replace('\\', '/', trim((string) $data['file_name'])));
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($filename === '' || !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file_name' => 'Select a ZIP, DOCX, DOC, PDF, XLSX or XLS file.',
            ]);
        }

        $size = (int) $data['file_size'];
        $maximum = (int) config('academic_repository.max_upload_size', 2 * 1024 * 1024 * 1024);
        if ($size < 1 || $size > $maximum) {
            throw ValidationException::withMessages([
                'file_size' => 'The selected file exceeds the 2 GB upload limit.',
            ]);
        }

        $chunkSize = max(64, min(8 * 1024 * 1024, (int) config('academic_repository.upload_chunk_size', 2 * 1024 * 1024)));
        $uploadId = (string) Str::uuid();
        $expiresAt = now()->addHours(max(1, (int) config('academic_repository.upload_expiry_hours', 48)));
        $manifest = [
            'schema' => 1,
            'id' => $uploadId,
            'actor_id' => $actor,
            'file_name' => $filename,
            'file_size' => $size,
            'extension' => $extension,
            'last_modified' => (int) ($data['last_modified'] ?? 0),
            'fingerprint' => (string) $data['fingerprint'],
            'chunk_size' => $chunkSize,
            'total_chunks' => (int) ceil($size / $chunkSize),
            'received' => [],
            'metadata' => $metadata,
            'status' => 'uploading',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        Storage::disk('local')->makeDirectory($this->directory($uploadId).'/chunks');
        $this->writeManifest($manifest);

        return $this->publicState($manifest);
    }

    public function status(string $uploadId, int $actor): array
    {
        return $this->withOwnedLock($uploadId, $actor, fn (array $manifest) => $this->publicState($manifest));
    }

    public function storeChunk(string $uploadId, int $index, UploadedFile $chunk, int $actor): array
    {
        return $this->withOwnedLock($uploadId, $actor, function (array $manifest) use ($uploadId, $index, $chunk) {
            if ($manifest['status'] === 'completed') {
                return $this->publicState($manifest);
            }
            abort_if($manifest['status'] === 'processing', 409, 'The archive is already being processed.');

            $totalChunks = (int) $manifest['total_chunks'];
            if ($index < 0 || $index >= $totalChunks) {
                throw ValidationException::withMessages(['index' => 'Invalid upload chunk.']);
            }

            $offset = $index * (int) $manifest['chunk_size'];
            $expected = min((int) $manifest['chunk_size'], (int) $manifest['file_size'] - $offset);
            if (!$chunk->isValid() || (int) $chunk->getSize() !== $expected) {
                throw ValidationException::withMessages([
                    'chunk' => 'The upload chunk is incomplete. Retry this part of the file.',
                ]);
            }

            $relativePath = $this->chunkPath($uploadId, $index);
            $stream = fopen($chunk->getRealPath(), 'rb');
            if ($stream === false || !Storage::disk('local')->put($relativePath, $stream)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                throw new RuntimeException('The upload chunk could not be stored.');
            }
            if (is_resource($stream)) {
                fclose($stream);
            }

            if ((int) Storage::disk('local')->size($relativePath) !== $expected) {
                Storage::disk('local')->delete($relativePath);
                throw new RuntimeException('The stored upload chunk failed verification.');
            }

            $manifest['received'][(string) $index] = [
                'size' => $expected,
                'checksum' => hash_file('sha256', Storage::disk('local')->path($relativePath)),
            ];
            $manifest['status'] = 'uploading';
            $manifest['updated_at'] = now()->toIso8601String();
            $this->writeManifest($manifest);

            return $this->publicState($manifest);
        });
    }

    public function complete(
        string $uploadId,
        int $actor,
        AcademicRepositoryIngestionService $ingestion
    ): array {
        $completion = $this->withOwnedLock($uploadId, $actor, function (array $manifest) {
            if ($manifest['status'] === 'completed' || $manifest['status'] === 'processing') {
                return ['manifest' => $manifest, 'should_process' => false];
            }

            $missing = $this->missingChunks($manifest);
            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'upload' => 'Some parts of the archive are missing. Resume the upload before processing.',
                ]);
            }

            $manifest['status'] = 'processing';
            $manifest['processing_started_at'] = now()->toIso8601String();
            $manifest['updated_at'] = now()->toIso8601String();
            unset($manifest['last_error']);
            $this->writeManifest($manifest);

            return ['manifest' => $manifest, 'should_process' => true];
        });
        $manifest = $completion['manifest'];

        if (!$completion['should_process']) {
            return $this->publicState($manifest);
        }

        $assembled = $this->directory($uploadId).'/assembled.'.$manifest['extension'];
        $disk = Storage::disk('local');

        try {
            $output = fopen($disk->path($assembled), 'wb');
            if ($output === false) {
                throw new RuntimeException('The archive could not be prepared for processing.');
            }

            try {
                for ($index = 0; $index < (int) $manifest['total_chunks']; $index++) {
                    $input = fopen($disk->path($this->chunkPath($uploadId, $index)), 'rb');
                    if ($input === false) {
                        throw new RuntimeException('An upload chunk is no longer available.');
                    }
                    try {
                        stream_copy_to_stream($input, $output);
                    } finally {
                        fclose($input);
                    }
                }
            } finally {
                fclose($output);
            }

            clearstatcache(true, $disk->path($assembled));
            if ((int) filesize($disk->path($assembled)) !== (int) $manifest['file_size']) {
                throw new RuntimeException('The reassembled archive failed size verification.');
            }

            $file = new UploadedFile(
                $disk->path($assembled),
                $manifest['file_name'],
                null,
                UPLOAD_ERR_OK,
                true
            );
            $import = $ingestion->ingest($file, (array) $manifest['metadata'], $actor);

            $completed = $this->withOwnedLock($uploadId, $actor, function (array $current) use ($import) {
                $current['status'] = 'completed';
                $current['repository_import_id'] = $import->id;
                $current['redirect_url'] = route('super.curriculum-sources.index');
                $current['completed_at'] = now()->toIso8601String();
                $current['updated_at'] = now()->toIso8601String();
                unset($current['last_error']);
                $this->writeManifest($current);

                return $current;
            });

            $disk->deleteDirectory($this->directory($uploadId).'/chunks');
            $disk->delete($assembled);

            return $this->publicState($completed);
        } catch (\Throwable $exception) {
            $disk->delete($assembled);
            $this->withOwnedLock($uploadId, $actor, function (array $current) use ($exception) {
                $current['status'] = 'failed';
                $current['last_error'] = Str::limit($exception->getMessage(), 300);
                $current['updated_at'] = now()->toIso8601String();
                unset($current['processing_started_at']);
                $this->writeManifest($current);

                return $current;
            });

            throw $exception;
        }
    }

    public function cancel(string $uploadId, int $actor): void
    {
        $this->withOwnedLock($uploadId, $actor, function (array $manifest) {
            abort_if($manifest['status'] === 'processing', 409, 'Processing has already started.');

            return $manifest;
        });

        Storage::disk('local')->deleteDirectory($this->directory($uploadId));
    }

    private function publicState(array $manifest): array
    {
        $received = collect(array_keys((array) ($manifest['received'] ?? [])))
            ->map(fn ($index) => (int) $index)
            ->sort()
            ->values();
        $uploadedBytes = collect((array) ($manifest['received'] ?? []))
            ->sum(fn ($chunk) => (int) ($chunk['size'] ?? 0));

        return [
            'id' => $manifest['id'],
            'file_name' => $manifest['file_name'],
            'file_size' => (int) $manifest['file_size'],
            'last_modified' => (int) ($manifest['last_modified'] ?? 0),
            'fingerprint' => $manifest['fingerprint'],
            'chunk_size' => (int) $manifest['chunk_size'],
            'total_chunks' => (int) $manifest['total_chunks'],
            'received' => $received->all(),
            'uploaded_bytes' => (int) $uploadedBytes,
            'progress' => (int) min(100, floor(($uploadedBytes / max(1, (int) $manifest['file_size'])) * 100)),
            'status' => $manifest['status'],
            'expires_at' => $manifest['expires_at'],
            'redirect_url' => $manifest['redirect_url'] ?? null,
            'repository_import_id' => $manifest['repository_import_id'] ?? null,
        ];
    }

    private function missingChunks(array $manifest): array
    {
        $missing = [];
        $disk = Storage::disk('local');
        for ($index = 0; $index < (int) $manifest['total_chunks']; $index++) {
            $record = $manifest['received'][(string) $index] ?? null;
            $path = $this->chunkPath($manifest['id'], $index);
            $expected = min(
                (int) $manifest['chunk_size'],
                (int) $manifest['file_size'] - ($index * (int) $manifest['chunk_size'])
            );
            if (!$record || !$disk->exists($path) || (int) $disk->size($path) !== $expected) {
                $missing[] = $index;
            }
        }

        return $missing;
    }

    private function withOwnedLock(string $uploadId, int $actor, callable $callback): mixed
    {
        $disk = Storage::disk('local');
        $manifestPath = $this->manifestPath($uploadId);
        abort_unless($disk->exists($manifestPath), 404);

        $handle = fopen($disk->path($this->directory($uploadId).'/.lock'), 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new RuntimeException('The upload session is busy. Retry shortly.');
        }

        try {
            $manifest = json_decode((string) $disk->get($manifestPath), true);
            abort_unless(is_array($manifest) && (int) ($manifest['actor_id'] ?? 0) === $actor, 404);
            if (($manifest['status'] ?? null) !== 'completed'
                && now()->greaterThan(\Illuminate\Support\Carbon::parse($manifest['expires_at']))) {
                abort(410, 'This upload session has expired. Start the upload again.');
            }

            return $callback($manifest);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function writeManifest(array $manifest): void
    {
        if (!Storage::disk('local')->put(
            $this->manifestPath($manifest['id']),
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        )) {
            throw new RuntimeException('The upload session could not be saved.');
        }
    }

    private function directory(string $uploadId): string
    {
        return self::ROOT.'/'.$uploadId;
    }

    private function manifestPath(string $uploadId): string
    {
        return $this->directory($uploadId).'/manifest.json';
    }

    private function chunkPath(string $uploadId, int $index): string
    {
        return $this->directory($uploadId).'/chunks/'.sprintf('%08d.part', $index);
    }

    private function purgeExpiredSessions(): void
    {
        $disk = Storage::disk('local');
        foreach ($disk->directories(self::ROOT) as $directory) {
            $manifestPath = $directory.'/manifest.json';
            if (!$disk->exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) $disk->get($manifestPath), true);
            if (is_array($manifest) && !empty($manifest['expires_at'])
                && now()->greaterThan(\Illuminate\Support\Carbon::parse($manifest['expires_at']))) {
                $disk->deleteDirectory($directory);
            }
        }
    }
}
