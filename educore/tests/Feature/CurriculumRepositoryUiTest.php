<?php

namespace Tests\Feature;

use App\Models\CurriculumSource;
use App\Models\CurriculumFragment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CurriculumRepositoryUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_workflows_have_three_dedicated_pages(): void
    {
        $indexRoute = Route::getRoutes()->getByName('super.curriculum-sources.index');
        $importRoute = Route::getRoutes()->getByName('super.curriculum-sources.create');
        $topicsRoute = Route::getRoutes()->getByName('super.curriculum-sources.topics.index');
        $storeRoute = Route::getRoutes()->getByName('super.curriculum-sources.store');
        $initiateRoute = Route::getRoutes()->getByName('super.curriculum-sources.uploads.initiate');
        $statusRoute = Route::getRoutes()->getByName('super.curriculum-sources.uploads.status');
        $chunkRoute = Route::getRoutes()->getByName('super.curriculum-sources.uploads.chunk');
        $completeRoute = Route::getRoutes()->getByName('super.curriculum-sources.uploads.complete');
        $cancelRoute = Route::getRoutes()->getByName('super.curriculum-sources.uploads.cancel');

        $this->assertNotNull($indexRoute);
        $this->assertNotNull($importRoute);
        $this->assertNotNull($topicsRoute);
        $this->assertNotNull($storeRoute);
        $this->assertNotNull($initiateRoute);
        $this->assertNotNull($statusRoute);
        $this->assertNotNull($chunkRoute);
        $this->assertNotNull($completeRoute);
        $this->assertNotNull($cancelRoute);
        $this->assertSame('super/curriculum-sources', $indexRoute->uri());
        $this->assertSame('super/curriculum-sources/import', $importRoute->uri());
        $this->assertSame('super/curriculum-sources/topics', $topicsRoute->uri());
        $this->assertSame('super/curriculum-sources/uploads/{upload}', $statusRoute->uri());
        $this->assertSame(['GET', 'HEAD'], $importRoute->methods());
        $this->assertSame(['GET', 'HEAD'], $topicsRoute->methods());
        $this->assertStringEndsWith('@create', $importRoute->getActionName());
        $this->assertStringEndsWith('@topics', $topicsRoute->getActionName());
    }

    public function test_resources_import_and_topic_mapping_views_are_separate(): void
    {
        $repository = file_get_contents(resource_path('views/curriculum-sources/index.blade.php'));
        $import = file_get_contents(resource_path('views/curriculum-sources/import.blade.php'));
        $topics = file_get_contents(resource_path('views/curriculum-sources/topics.blade.php'));
        $browser = file_get_contents(resource_path('views/curriculum-sources/_browser_script.blade.php'));

        $this->assertStringContainsString("route('super.curriculum-sources.create')", $repository);
        $this->assertStringContainsString('data-class-target', $repository);
        $this->assertStringContainsString('data-term-target', $repository);
        $this->assertStringContainsString("@include('curriculum-sources._browser_script'", $repository);
        $this->assertStringContainsString('panels.forEach(panel => panel.hidden = panel.id !== tab.dataset.termTarget);', $browser);
        $this->assertStringContainsString("classPanel.scrollIntoView({behavior:'smooth',block:'start'});", $browser);
        $this->assertStringContainsString("localStorage.setItem(storageKey", $browser);
        $this->assertStringNotContainsString('name="source_files[]"', $repository);
        $this->assertStringNotContainsString('name="subtopics_text"', $repository);
        $this->assertStringContainsString('name="source_files[]"', $import);
        $this->assertStringContainsString('id="uploadProgress"', $import);
        $this->assertStringContainsString("route('super.curriculum-sources.store')", $import);
        $this->assertStringContainsString('value="lesson_note"', $import);
        $this->assertStringContainsString('Class → Subject → Term', $import);
        $this->assertStringContainsString('id="pauseUpload"', $import);
        $this->assertStringContainsString('id="resumeUpload"', $import);
        $this->assertStringContainsString('id="retryUpload"', $import);
        $this->assertStringContainsString('Saved upload found', $import);
        $this->assertStringContainsString("route('super.curriculum-sources.uploads.initiate')", $import);
        $this->assertStringNotContainsString('name="subtopics_text"', $import);
        $this->assertStringContainsString('name="subtopics_text"', $topics);
        $this->assertStringContainsString("route('super.curriculum-sources.topics.store')", $topics);
        $this->assertStringNotContainsString('name="source_files[]"', $topics);
    }

    public function test_repository_index_handles_legacy_string_metadata(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        CurriculumSource::create([
            'tenant_id' => null,
            'authority' => 'OTHER',
            'source_type' => 'lesson_note',
            'title' => 'Legacy lesson note',
            'version' => '2026',
            'original_filename' => 'legacy-note.docx',
            'created_by' => $admin->id,
            'metadata' => '{"format":"docx"}',
        ]);

        $this->actingAs($admin)
            ->get(route('super.curriculum-sources.index'))
            ->assertOk()
            ->assertSee('Legacy lesson note');
    }

    public function test_repository_is_grouped_by_class_term_and_subject_with_failed_extraction_action(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        CurriculumSource::create([
            'tenant_id' => null, 'authority' => 'OTHER', 'source_type' => 'lesson_note',
            'title' => 'Homeostasis', 'version' => '2026', 'original_filename' => 'homeostasis.pdf',
            'created_by' => $admin->id, 'extraction_status' => 'failed', 'index_status' => 'failed',
            'needs_review' => true, 'metadata' => ['class_label'=>'SS 2','term_label'=>'Second Term','subject_label'=>'Biology'],
        ]);

        $this->actingAs($admin)->get(route('super.curriculum-sources.index'))
            ->assertOk()
            ->assertSeeInOrder(['SS 2', 'Second Term', 'Biology', 'Homeostasis'])
            ->assertSee('Extraction failed')
            ->assertSee('Re-index')
            ->assertSee('data-subject-select', false)
            ->assertSee(route('super.curriculum-sources.bulk'), false);
    }

    public function test_bulk_activation_activates_indexed_resources_and_skips_failed_resources(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $indexed = CurriculumSource::create([
            'tenant_id'=>null,'authority'=>'OTHER','source_type'=>'lesson_note','title'=>'Indexed note','version'=>'2026',
            'created_by'=>$admin->id,'extraction_status'=>'extracted','index_status'=>'indexed','needs_review'=>true,
        ]);
        CurriculumFragment::create([
            'curriculum_source_id'=>$indexed->id,'topic'=>'Cells','content'=>str_repeat('Indexed lesson content. ', 10),
        ]);
        $failed = CurriculumSource::create([
            'tenant_id'=>null,'authority'=>'OTHER','source_type'=>'lesson_note','title'=>'Failed note','version'=>'2026',
            'created_by'=>$admin->id,'extraction_status'=>'failed','index_status'=>'failed','needs_review'=>true,
        ]);

        $this->actingAs($admin)->post(route('super.curriculum-sources.bulk'), [
            'action'=>'activate', 'source_ids'=>[$indexed->id, $failed->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('curriculum_sources', ['id'=>$indexed->id,'is_active'=>true,'review_status'=>'approved','needs_review'=>false]);
        $this->assertDatabaseHas('curriculum_sources', ['id'=>$failed->id,'is_active'=>false]);
    }

    public function test_resumable_archive_upload_recovers_chunks_and_completes_import(): void
    {
        Storage::fake('local');
        config([
            'academic_repository.upload_chunk_size' => 96,
            'academic_repository.max_upload_size' => 4096,
        ]);
        $admin = User::factory()->create(['is_super_admin' => true]);
        $content = str_repeat(
            'This biology lesson note explains living organisms, their characteristics and practical examples. ',
            12
        );
        $fingerprint = 'SS1 Biology Term 1.doc:'.strlen($content).':123456';
        $metadata = [
            'authority' => 'OTHER',
            'source_type' => 'lesson_note',
            'rights_status' => 'institution_authorised',
            'column_mapping_json' => '{}',
        ];

        $created = $this->actingAs($admin)->postJson(
            route('super.curriculum-sources.uploads.initiate'),
            array_merge($metadata, [
                'file_name' => 'SS1 Biology Term 1.doc',
                'file_size' => strlen($content),
                'last_modified' => 123456,
                'fingerprint' => $fingerprint,
            ])
        )->assertCreated()->json();

        $this->assertGreaterThan(1, $created['total_chunks']);
        $uploadId = $created['id'];
        $chunkSize = $created['chunk_size'];
        $chunks = str_split($content, $chunkSize);

        $this->post(route('super.curriculum-sources.uploads.chunk', $uploadId), [
            'index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('first.part', $chunks[0]),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('received.0', 0);

        $this->getJson(route('super.curriculum-sources.uploads.status', $uploadId))
            ->assertOk()
            ->assertJsonPath('uploaded_bytes', strlen($chunks[0]))
            ->assertJsonPath('status', 'uploading');

        foreach (array_slice($chunks, 1, null, true) as $index => $chunk) {
            $this->post(route('super.curriculum-sources.uploads.chunk', $uploadId), [
                'index' => $index,
                'chunk' => UploadedFile::fake()->createWithContent("chunk-{$index}.part", $chunk),
            ], ['Accept' => 'application/json'])->assertOk();
        }

        $this->postJson(route('super.curriculum-sources.uploads.complete', $uploadId))
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('progress', 100);
        $this->assertDatabaseHas('curriculum_sources', [
            'tenant_id' => null,
            'original_filename' => 'SS1 Biology Term 1.doc',
            'source_type' => 'lesson_note',
        ]);
        $this->getJson(route('super.curriculum-sources.uploads.status', $uploadId))
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $otherAdmin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($otherAdmin)
            ->getJson(route('super.curriculum-sources.uploads.status', $uploadId))
            ->assertNotFound();
    }
}
