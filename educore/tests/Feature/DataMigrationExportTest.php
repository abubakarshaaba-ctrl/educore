<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\CanonicalExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;
use ZipArchive;

class DataMigrationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_selective_export_is_tenant_scoped_manifested_and_verified(): void
    {
        Storage::fake('local');
        config(['data_migration_export.disk' => 'local']);
        [$a,$admin] = $this->tenant('export-a');
        [$b] = $this->tenant('export-b');
        Student::create(['tenant_id' => $a->id, 'admission_number' => 'A-1', 'first_name' => 'Ada', 'last_name' => 'One']);
        Student::create(['tenant_id' => $b->id, 'admission_number' => 'B-1', 'first_name' => 'Bola', 'last_name' => 'Two']);
        $package = app(CanonicalExportService::class)->export($a, $admin, ['students']);
        $this->assertTrue(app(CanonicalExportService::class)->verify($package));
        $this->assertSame(1, $package->manifest['datasets']['students']['records']);
        $this->assertSame(['students'], $package->scope);
        $this->assertDatabaseHas('audit_logs', ['action' => 'data_migration.export_created', 'tenant_id' => $a->id]);
        $path = Storage::disk('local')->path($package->storage_path);
        $zip = new ZipArchive;
        $zip->open($path);
        $data = $zip->getFromName('datasets/students.jsonl');
        $zip->close();
        $this->assertStringContainsString('A-1', $data);
        $this->assertStringNotContainsString('B-1', $data);
        $this->assertStringNotContainsString('tenant_id', $data);
    }

    public function test_cross_tenant_export_and_unknown_scope_are_rejected(): void
    {
        [$a] = $this->tenant('export-c');
        [,$other] = $this->tenant('export-d');
        $this->expectException(InvalidArgumentException::class);
        app(CanonicalExportService::class)->export($a, $other, ['secrets']);
    }

    private function tenant(string $slug): array
    {
        $t = Tenant::create(['name' => $slug, 'slug' => $slug, 'status' => Tenant::STATUS_ACTIVE]);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => "$slug@test.local", 'password' => bcrypt('x'), 'role' => 'admin', 'is_active' => true]);

        return [$t, $u];
    }
}
