<?php

namespace Tests\Feature;

use App\Services\AuthenticatedIdentityAssetStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthenticatedIdentityAssetStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(AuthenticatedIdentityAssetStorage::PRIVATE_DISK);
        Storage::fake(AuthenticatedIdentityAssetStorage::LEGACY_DISK);
    }

    public function test_only_identity_and_attendance_asset_paths_are_accepted(): void
    {
        $storage = app(AuthenticatedIdentityAssetStorage::class);

        $this->assertSame('passports/3/9/photo.jpg', $storage->normalize('/storage/passports/3/9/photo.jpg'));
        $this->assertSame('signatures/3/sign.png', $storage->normalize('signatures/3/sign.png'));
        $this->assertSame('attendance-photos/3/20260909/proxy.jpg', $storage->normalize('attendance-photos/3/20260909/proxy.jpg'));
        $this->assertNull($storage->normalize('logos/3/logo.png'));
        $this->assertNull($storage->normalize('passports/3/../secret.jpg'));
        $this->assertNull($storage->normalize('../../.env'));
        $this->assertNull($storage->normalize("signatures/3/bad\0name.png"));
    }

    public function test_legacy_identity_asset_is_verified_then_moved_to_private_storage(): void
    {
        $storage = app(AuthenticatedIdentityAssetStorage::class);
        $path = 'passports/3/9/photo.jpg';
        Storage::disk(AuthenticatedIdentityAssetStorage::LEGACY_DISK)->put($path, 'image-content');

        $this->assertSame('migrated', $storage->migrateToPrivate($path));
        Storage::disk(AuthenticatedIdentityAssetStorage::PRIVATE_DISK)->assertExists($path);
        Storage::disk(AuthenticatedIdentityAssetStorage::LEGACY_DISK)->assertMissing($path);
        $this->assertSame('image-content', $storage->read($path));
        $this->assertSame('already_private', $storage->migrateToPrivate($path));
    }

    public function test_private_storage_is_preferred_with_legacy_fallback(): void
    {
        $storage = app(AuthenticatedIdentityAssetStorage::class);
        $privatePath = 'signatures/4/private.png';
        $legacyPath = 'passports/4/8/legacy.jpg';
        Storage::disk(AuthenticatedIdentityAssetStorage::PRIVATE_DISK)->put($privatePath, 'private-signature');
        Storage::disk(AuthenticatedIdentityAssetStorage::LEGACY_DISK)->put($legacyPath, 'legacy-photo');

        $this->assertTrue($storage->exists($privatePath));
        $this->assertTrue($storage->exists($legacyPath));
        $this->assertSame('private-signature', $storage->read($privatePath));
        $this->assertSame('legacy-photo', $storage->read($legacyPath));
        $this->assertSame(
            Storage::disk(AuthenticatedIdentityAssetStorage::PRIVATE_DISK)->path($privatePath),
            $storage->resolveAbsolutePath($privatePath),
        );
        $this->assertSame(
            Storage::disk(AuthenticatedIdentityAssetStorage::LEGACY_DISK)->path($legacyPath),
            $storage->resolveAbsolutePath($legacyPath),
        );
    }
}
