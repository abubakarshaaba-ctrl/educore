<?php

namespace Tests\Feature;

use App\Services\SensitiveDocumentStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SensitiveDocumentStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(SensitiveDocumentStorage::PRIVATE_DISK);
        Storage::fake(SensitiveDocumentStorage::LEGACY_DISK);
    }

    public function test_sensitive_paths_are_normalized_and_path_traversal_is_rejected(): void
    {
        $storage = app(SensitiveDocumentStorage::class);

        $this->assertSame('admissions/1/2/document.pdf', $storage->normalize('/storage/admissions/1/2/document.pdf'));
        $this->assertSame('recruitment/9/resume.pdf', $storage->normalize('recruitment/9/resume.pdf'));
        $this->assertNull($storage->normalize('../admissions/1/secret.pdf'));
        $this->assertNull($storage->normalize('admissions/1/../secret.pdf'));
        $this->assertNull($storage->normalize('logos/1/school.png'));
        $this->assertNull($storage->normalize("admissions/1/bad\0name.pdf"));
    }

    public function test_legacy_public_document_is_verified_then_moved_to_private_storage(): void
    {
        $storage = app(SensitiveDocumentStorage::class);
        $path = 'admissions/1/2/birth-certificate.pdf';
        Storage::disk(SensitiveDocumentStorage::LEGACY_DISK)->put($path, 'sensitive-content');

        $this->assertSame('migrated', $storage->migrateToPrivate($path));
        Storage::disk(SensitiveDocumentStorage::PRIVATE_DISK)->assertExists($path);
        Storage::disk(SensitiveDocumentStorage::LEGACY_DISK)->assertMissing($path);
        $this->assertSame('sensitive-content', Storage::disk(SensitiveDocumentStorage::PRIVATE_DISK)->get($path));
        $this->assertSame('already_private', $storage->migrateToPrivate($path));
    }

    public function test_authenticated_resolution_prefers_private_storage_but_supports_legacy_files(): void
    {
        $storage = app(SensitiveDocumentStorage::class);
        $privatePath = 'recruitment/2/private.pdf';
        $legacyPath = 'recruitment/2/legacy.pdf';
        Storage::disk(SensitiveDocumentStorage::PRIVATE_DISK)->put($privatePath, 'private');
        Storage::disk(SensitiveDocumentStorage::LEGACY_DISK)->put($legacyPath, 'legacy');

        $this->assertSame(
            Storage::disk(SensitiveDocumentStorage::PRIVATE_DISK)->path($privatePath),
            $storage->resolveAbsolutePath($privatePath),
        );
        $this->assertSame(
            Storage::disk(SensitiveDocumentStorage::LEGACY_DISK)->path($legacyPath),
            $storage->resolveAbsolutePath($legacyPath),
        );
        $this->assertNull($storage->resolveAbsolutePath('admissions/1/missing.pdf'));
        $this->assertNull($storage->resolveAbsolutePath('../../.env'));
    }
}
