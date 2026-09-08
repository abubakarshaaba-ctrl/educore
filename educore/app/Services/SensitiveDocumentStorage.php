<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Storage boundary for applicant/admission documents that may contain PII.
 *
 * New document rows can point to the same relative path regardless of disk.
 * The service migrates legacy public-disk files to the private local disk and
 * keeps authenticated downloads backwards-compatible while migration is in
 * progress.
 */
class SensitiveDocumentStorage
{
    public const PRIVATE_DISK = 'local';
    public const LEGACY_DISK = 'public';

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

        if (!str_starts_with($path, 'admissions/') && !str_starts_with($path, 'recruitment/')) {
            return null;
        }

        return $path;
    }

    /**
     * Resolve an absolute server-side path for an authenticated download.
     * Private storage is authoritative; public storage is legacy fallback only.
     */
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

    /**
     * Move one legacy public file into private storage without changing its
     * database path. The operation is idempotent and deletes the public copy
     * only after the private write is verified.
     *
     * @return string one of: migrated, already_private, missing, invalid, failed
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
            // Remove a stale duplicate only when a private authoritative copy exists.
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

        if (!$written || !$private->exists($normalized)) {
            return 'failed';
        }

        $publicSize = $public->size($normalized);
        $privateSize = $private->size($normalized);
        if ($publicSize !== $privateSize) {
            $private->delete($normalized);
            return 'failed';
        }

        if (!$public->delete($normalized)) {
            // Keeping both copies is safer than deleting an unverified source.
            return 'failed';
        }

        return 'migrated';
    }
}
