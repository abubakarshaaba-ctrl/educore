<?php

namespace Tests\Feature;

use App\Enums\DataMigrationStatus;
use App\Exceptions\UnsupportedMigrationSource;
use App\Models\MigrationCheckpoint;
use App\Models\MigrationDataset;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataMigration\ImmutableSourceStorage;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationIngestionService;
use App\Services\DataMigration\MigrationRowStager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DataMigrationStructuredIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['data_migration.source_disk' => 'local', 'data_migration.staging_chunk_rows' => 2, 'data_migration.spreadsheet_chunk_rows' => 2]);
    }

    public function test_csv_is_streamed_into_lossless_staging_with_checkpoints(): void
    {
        [$migration, $admin] = $this->batch('csv-school');
        $this->source($migration, $admin, 'students.csv', "admission_number,name,legacy_special_code\nA001,Ada,KEEP-1\nA002,Tunde,KEEP-2\nA003,Zainab,KEEP-3\n");

        $result = app(MigrationIngestionService::class)->ingest($migration, $admin);

        $this->assertSame(DataMigrationStatus::Extracted, $result->status);
        $this->assertSame(3, $result->total_source_rows);
        $this->assertDatabaseHas('migration_rows', ['row_number' => 3]);
        $this->assertSame('KEEP-2', $result->rows()->where('row_number', 2)->first()->raw_payload['legacy_special_code']);
        $this->assertGreaterThanOrEqual(2, MigrationCheckpoint::where('migration_id', $result->id)->count());
    }

    public function test_json_jsonl_and_xml_sources_are_supported(): void
    {
        foreach ([
            ['json-school', 'students.json', json_encode(['students' => [['id' => 'A1', 'name' => 'Ada'], ['id' => 'A2', 'name' => 'Tunde']]], JSON_THROW_ON_ERROR), 2],
            ['jsonl-school', 'students.jsonl', "{\"id\":\"A1\",\"name\":\"Ada\"}\n{\"id\":\"A2\",\"name\":\"Tunde\"}\n", 2],
            ['xml-school', 'students.xml', '<?xml version="1.0"?><students><student><id>A1</id><name>Ada</name></student><student><id>A2</id><name>Tunde</name></student></students>', 2],
        ] as [$slug, $filename, $content, $expected]) {
            [$migration, $admin] = $this->batch($slug);
            $this->source($migration, $admin, $filename, $content);
            $result = app(MigrationIngestionService::class)->ingest($migration, $admin);
            $this->assertSame($expected, $result->total_source_rows, $filename);
            $this->assertSame(DataMigrationStatus::Extracted, $result->status);
        }
    }

    public function test_xlsx_is_read_in_bounded_chunks(): void
    {
        [$migration, $admin] = $this->batch('xlsx-school');
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setTitle('Students')->fromArray([
            ['admission_number', 'name'], ['A001', 'Ada'], ['A002', 'Tunde'], ['A003', 'Zainab'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'educore-xlsx-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $upload = new UploadedFile($path, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        app(ImmutableSourceStorage::class)->preserve($migration, $upload, $admin);

        $result = app(MigrationIngestionService::class)->ingest($migration, $admin);

        $this->assertSame(3, $result->total_source_rows);
        $this->assertDatabaseHas('migration_datasets', ['migration_id' => $result->id, 'source_name' => 'Students', 'staged_row_count' => 3]);
        @unlink($path);
    }

    public function test_tsv_xls_and_ods_are_supported(): void
    {
        [$tsvMigration, $tsvAdmin] = $this->batch('tsv-school');
        $this->source($tsvMigration, $tsvAdmin, 'students.tsv', "id\tname\nA1\tAda\nA2\tTunde\n");
        $this->assertSame(2, app(MigrationIngestionService::class)->ingest($tsvMigration, $tsvAdmin)->total_source_rows);

        foreach ([['xls-school', 'students.xls', Xls::class], ['ods-school', 'students.ods', Ods::class]] as [$slug, $filename, $writerClass]) {
            [$migration, $admin] = $this->batch($slug);
            $spreadsheet = new Spreadsheet;
            $spreadsheet->getActiveSheet()->setTitle('Students')->fromArray([['id', 'name'], ['A1', 'Ada'], ['A2', 'Tunde']]);
            $path = tempnam(sys_get_temp_dir(), 'educore-sheet-').'.'.pathinfo($filename, PATHINFO_EXTENSION);
            (new $writerClass($spreadsheet))->save($path);
            $spreadsheet->disconnectWorksheets();
            app(ImmutableSourceStorage::class)->preserve($migration, new UploadedFile($path, $filename, null, null, true), $admin);
            $this->assertSame(2, app(MigrationIngestionService::class)->ingest($migration, $admin)->total_source_rows, $filename);
            @unlink($path);
        }
    }

    public function test_resume_uses_checkpoint_and_does_not_duplicate_staged_rows(): void
    {
        [$migration, $admin] = $this->batch('resume-school');
        $file = $this->source($migration, $admin, 'students.csv', "id,name\nA1,Ada\nA2,Tunde\nA3,Zainab\n");
        $dataset = MigrationDataset::create([
            'migration_id' => $migration->id, 'migration_file_id' => $file->id, 'source_name' => 'students',
            'classification_status' => 'unclassified', 'source_schema' => ['headers' => ['id', 'name']],
        ]);
        app(MigrationRowStager::class)->stage($migration, $dataset, 1, ['id' => 'A1', 'name' => 'Ada']);
        MigrationCheckpoint::create(['migration_id' => $migration->id, 'dataset_id' => $dataset->id, 'stage' => 'extraction', 'last_row_number' => 1, 'processed_rows' => 1, 'created_at' => now()]);
        $migration->update(['status' => DataMigrationStatus::Failed]);

        $result = app(MigrationIngestionService::class)->ingest($migration->refresh(), $admin);

        $this->assertSame(3, $result->rows()->count());
        $this->assertSame(3, $result->rows()->distinct('row_number')->count('row_number'));
    }

    public function test_unrecognised_binary_is_rejected_and_batch_is_failed_with_issue(): void
    {
        [$migration, $admin] = $this->batch('bad-source-school');
        $this->source($migration, $admin, 'payload.bin', "\x00\x01\x02\x03not-a-table");

        try {
            app(MigrationIngestionService::class)->ingest($migration, $admin);
            $this->fail('Unsupported source was accepted.');
        } catch (UnsupportedMigrationSource) {
            $this->assertSame(DataMigrationStatus::Failed, $migration->refresh()->status);
            $this->assertDatabaseHas('migration_issues', ['migration_id' => $migration->id, 'severity' => 'critical', 'category' => 'source_ingestion']);
        }
    }

    private function batch(string $slug): array
    {
        $tenant = Tenant::create(['name' => str($slug)->headline(), 'slug' => $slug, 'status' => Tenant::STATUS_ACTIVE]);
        $admin = User::create(['tenant_id' => $tenant->id, 'name' => 'Migration Admin', 'email' => "{$slug}@example.test", 'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true, 'is_super_admin' => false]);

        return [app(MigrationBatchService::class)->create($tenant, $admin, 'inbound', 'full_migration'), $admin];
    }

    private function source($migration, User $admin, string $name, string $content)
    {
        return app(ImmutableSourceStorage::class)->preserve($migration, UploadedFile::fake()->createWithContent($name, $content), $admin);
    }
}
