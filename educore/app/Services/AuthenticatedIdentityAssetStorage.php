<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Authenticated storage boundary for staff passport photos and school signing assets.
 *
 * New uploads are stored on the private local disk. Existing public-disk files remain
 * readable while they are migrated, but callers never need to expose a /storage URL.
 */
class AuthenticatedIdentityAssetStorage
{
    public const PRIVATE_DISK = 'local';
    public const LEGACY_DISK = 'public';

    private const PREFIXES = ['passports/', 'signatures/'];

    public function normalize(?string $path): ?string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = preg_replace('#^storage/#', '', ltrim($path, '/'));

        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        $segments = explode('/', $path);
        if (in_array('..', $segments, true) || in_array('.', $segments, true)) {
            return null;
        }

        if (!collect(self::PREFIXES)->contains(fn (string $prefix): bool => str_starts_with($path, $prefix))) {
            return null;
        }

        return $path;
    }

    public function exists(?string $path): bool
    {
        $normalized = $this->normalize($path);
        if ($normalized === null) {
            return false;
        }

        return Storage::disk(self::PRIVATE_DISK)->exists($normalized)
            || Storage::disk(self::LEGACY_DISK)->exists($normalized);
    }

    public function resolveAbsolutePath(?string $path): ?string
    {
        $normalized = $this->normalize($path);
        if ($normalized === null) {
            return null;
        }

        if (Storage::disk(self::PRIVATE_DISK)->exists($normalized)) {
            return Storage::disk(self::PRIVATE_DISK)->path($normalized);
        }

        if (Storage::disk(self::LEGACY_DISK)->exists($normalized)) {
            return Storage::disk(self::LEGACY_DISK)->path($normalized);
        }

        return null;
    }

    public function store(UploadedFile $file, string $directory): string
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');
        $candidate = $directory.'/placeholder';
        if ($this->normalize($candidate) === null) {
            throw new \InvalidArgumentException('Unsupported identity asset directory.');
        }

        return $file->store($directory, self::PRIVATE_DISK);
    }

    public function delete(?string $path): void
    {
        $normalized = $this->normalize($path);
        if ($normalized === null) {
            return;
        }

        foreach ([self::PRIVATE_DISK, self::LEGACY_DISK] as $disk) {
            if (Storage::disk($disk)->exists($normalized)) {
                Storage::disk($disk)->delete($normalized);
            }
        }
    }

    /**
     * Idempotently move a legacy public identity asset into private storage.
     */
    public function migrateToPrivate(?string $path): string
    {
        $normalized = $this->normalize($path);
        if ($normalized === null) {
            return 'invalid';
        }

        $private = Storage::disk(self::PRIVATE_DISK);
        $public = Storage::disk(self::LEGACY_DISK);

        if ($private->exists($normalized)) {
            if ($public->exists($normalized)) {
                $public->delete($normalized);
            }
            return 'already_private';
        }

        if (!$public->exists($normalized)) {
            return 'missing';
        }

        $stream = $public->readStream($normalized);
        if (!is_resource($stream)) {
            return 'failed';
        }

        try {
            $written = $private->writeStream($normalized, $stream);
        } finally {
            fclose($stream);
        }

        if (!$written || !$private->exists($normalized) || $private->size($normalized) !== $public->size($normalized)) {
            if ($private->exists($normalized)) {
                $private->delete($normalized);
            }
            return 'failed';
        }

        return $public->delete($normalized) ? 'migrated' : 'failed';
    }
}
