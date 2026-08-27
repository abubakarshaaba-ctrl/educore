<?php

namespace Tests\Feature;

use App\Models\CurriculumFragment;
use App\Models\CurriculumSource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademicRepositoryReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_has_read_only_school_routes_and_persistent_browser_state(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('academic-repository.index'));
        $this->assertNotNull(Route::getRoutes()->getByName('academic-repository.show'));
        $this->assertNotNull(Route::getRoutes()->getByName('academic-repository.download'));

        $manager = file_get_contents(resource_path('views/curriculum-sources/index.blade.php'));
        $reader = file_get_contents(resource_path('views/academic-repository/index.blade.php'));
        $browser = file_get_contents(resource_path('views/curriculum-sources/_browser_script.blade.php'));

        $this->assertStringContainsString('data-class-key', $manager);
        $this->assertStringContainsString('data-term-key', $manager);
        $this->assertStringContainsString('data-selection-class-field', $reader);
        $this->assertStringContainsString('data-selection-term-field', $reader);
        $this->assertStringContainsString('localStorage.setItem(storageKey', $browser);
        $this->assertStringContainsString("url.searchParams.set('selected_class'", $browser);
        $this->assertStringContainsString("url.searchParams.set('selected_term'", $browser);
    }

    public function test_school_admin_and_teacher_can_read_and_download_only_active_platform_resources(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create(['name' => 'Greenfield Academy', 'slug' => 'greenfield-reader', 'status' => Tenant::STATUS_ACTIVE]);
        $owner = User::factory()->create(['is_super_admin' => true, 'is_active' => true]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'is_active' => true]);
        $teacher = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'subject_teacher', 'is_active' => true]);
        $accountant = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'accountant', 'is_active' => true]);

        Storage::disk('local')->put('academic-repository/originals/ss1/biology/first-term/biology.docx', 'original lesson note');
        $source = CurriculumSource::create([
            'tenant_id' => null,
            'authority' => 'OTHER',
            'source_type' => 'lesson_note',
            'title' => 'Introduction to Biology',
            'version' => '2026',
            'original_filename' => 'SS1 Biology Week 1.docx',
            'source_file_path' => 'academic-repository/originals/ss1/biology/first-term/biology.docx',
            'file_size' => 20,
            'created_by' => $owner->id,
            'extraction_status' => 'extracted',
            'index_status' => 'indexed',
            'is_active' => true,
            'needs_review' => false,
            'metadata' => ['class_label' => 'SS 1', 'term_label' => 'First Term', 'subject_label' => 'Biology'],
        ]);
        CurriculumFragment::create([
            'curriculum_source_id' => $source->id,
            'topic' => 'Introduction to Biology',
            'content' => 'Biology is the scientific study of living organisms and their environment.',
            'sequence' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('academic-repository.index'))
            ->assertOk()
            ->assertSeeInOrder(['SS 1', 'First Term', 'Biology', 'Introduction to Biology'])
            ->assertDontSee('Activate')
            ->assertDontSee('Remove');

        $this->actingAs($teacher)
            ->get(route('academic-repository.show', $source))
            ->assertOk()
            ->assertSee('Biology is the scientific study of living organisms')
            ->assertSee('Download original');

        $this->actingAs($teacher)
            ->get(route('academic-repository.download', $source))
            ->assertDownload('SS1 Biology Week 1.docx');

        $this->actingAs($accountant)
            ->get(route('academic-repository.index'))
            ->assertForbidden();
    }

    public function test_inactive_and_school_owned_sources_are_not_exposed_to_readers(): void
    {
        $tenant = Tenant::create(['name' => 'Reader School', 'slug' => 'reader-school', 'status' => Tenant::STATUS_ACTIVE]);
        $owner = User::factory()->create(['is_super_admin' => true, 'is_active' => true]);
        $teacher = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'form_teacher', 'is_active' => true]);

        $inactive = $this->source($owner, null, 'Inactive platform note', false);
        $schoolOwned = $this->source($owner, $tenant->id, 'Private school note', true);

        $this->actingAs($teacher)
            ->get(route('academic-repository.index'))
            ->assertOk()
            ->assertDontSee('Inactive platform note')
            ->assertDontSee('Private school note');

        $this->actingAs($teacher)->get(route('academic-repository.show', $inactive))->assertNotFound();
        $this->actingAs($teacher)->get(route('academic-repository.show', $schoolOwned))->assertNotFound();
    }

    private function source(User $owner, ?int $tenantId, string $title, bool $active): CurriculumSource
    {
        $source = CurriculumSource::create([
            'tenant_id' => $tenantId,
            'authority' => 'OTHER',
            'source_type' => 'lesson_note',
            'title' => $title,
            'version' => '2026',
            'created_by' => $owner->id,
            'extraction_status' => 'extracted',
            'index_status' => 'indexed',
            'is_active' => $active,
            'metadata' => ['class_label' => 'SS 2', 'term_label' => 'Second Term', 'subject_label' => 'Chemistry'],
        ]);
        CurriculumFragment::create([
            'curriculum_source_id' => $source->id,
            'topic' => $title,
            'content' => str_repeat('Approved lesson content. ', 8),
        ]);

        return $source;
    }
}
