<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ClassLevelSubject;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumComposite;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumIntegration;
use App\Models\ParallelCurriculumSubject;
use App\Models\ReportCardPublication;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use App\Services\ParallelCurriculumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParallelCurriculumLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabling_mapping_clears_unpublished_derived_scores(): void
    {
        $fixture = $this->mappingFixture();

        $result = app(ParallelCurriculumService::class)
            ->deactivateIntegration($fixture['integration']);

        $this->assertFalse($fixture['integration']->fresh()->is_active);
        $this->assertSame(1, $result['recomputed']);
        $this->assertSame(1, $result['cleared']);
        $this->assertSame(0, $result['preserved']);

        $this->assertDatabaseMissing('scores', [
            'id' => $fixture['score']->id,
        ]);

        $composite = $fixture['composite']->fresh();
        $this->assertNull($composite->parallel_curriculum_integration_id);
        $this->assertNull($composite->destination_subject_id);
        $this->assertNull($composite->average_score);
        $this->assertSame('unmapped', $composite->sync_status);
    }

    public function test_disabling_mapping_preserves_derived_scores_for_published_conventional_result(): void
    {
        $fixture = $this->mappingFixture();

        ReportCardPublication::create([
            'tenant_id' => $fixture['tenant']->id,
            'class_arm_id' => $fixture['classArm']->id,
            'term_id' => $fixture['term']->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $result = app(ParallelCurriculumService::class)
            ->deactivateIntegration($fixture['integration']);

        $this->assertFalse($fixture['integration']->fresh()->is_active);
        $this->assertSame(0, $result['cleared']);
        $this->assertSame(1, $result['preserved']);

        $this->assertDatabaseHas('scores', [
            'id' => $fixture['score']->id,
            'score_source' => ParallelCurriculumService::SCORE_SOURCE,
            'source_reference_id' => $fixture['composite']->id,
        ]);

        $composite = $fixture['composite']->fresh();
        $this->assertSame($fixture['integration']->id, $composite->parallel_curriculum_integration_id);
        $this->assertSame($fixture['subject']->id, $composite->destination_subject_id);
        $this->assertSame('synced', $composite->sync_status);
        $this->assertSame(80.0, $composite->average_score);
    }

    public function test_unpublishing_conventional_result_cleans_preserved_score_from_inactive_mapping(): void
    {
        $fixture = $this->mappingFixture();
        $service = app(ParallelCurriculumService::class);

        $publication = ReportCardPublication::create([
            'tenant_id' => $fixture['tenant']->id,
            'class_arm_id' => $fixture['classArm']->id,
            'term_id' => $fixture['term']->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $service->deactivateIntegration($fixture['integration']);

        $this->assertDatabaseHas('scores', ['id' => $fixture['score']->id]);
        $this->assertSame(
            $fixture['integration']->id,
            $fixture['composite']->fresh()->parallel_curriculum_integration_id
        );

        $publication->update([
            'status' => 'draft',
            'archived_at' => now(),
        ]);

        $cleaned = $service->cleanupAfterConventionalUnpublish(
            $fixture['tenant']->id,
            [$fixture['classArm']->id],
            $fixture['term']->id
        );

        $this->assertSame(1, $cleaned);
        $this->assertDatabaseMissing('scores', ['id' => $fixture['score']->id]);

        $composite = $fixture['composite']->fresh();
        $this->assertNull($composite->parallel_curriculum_integration_id);
        $this->assertNull($composite->destination_subject_id);
        $this->assertSame('unmapped', $composite->sync_status);
    }

    public function test_published_conventional_result_locks_mapping_configuration(): void
    {
        $fixture = $this->mappingFixture();
        $service = app(ParallelCurriculumService::class);

        $this->assertFalse(
            $service->integrationHasPublishedDependencies($fixture['integration'])
        );

        ReportCardPublication::create([
            'tenant_id' => $fixture['tenant']->id,
            'class_arm_id' => $fixture['classArm']->id,
            'term_id' => $fixture['term']->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertTrue(
            $service->integrationHasPublishedDependencies($fixture['integration'])
        );
    }

    public function test_manual_sync_mapping_change_clears_stale_unpublished_derived_score(): void
    {
        $fixture = $this->mappingFixture();
        $fixture['integration']->update(['auto_sync' => false]);

        $summary = app(ParallelCurriculumService::class)
            ->reconcileIntegration($fixture['integration']->fresh(), true);

        $this->assertDatabaseMissing('scores', [
            'id' => $fixture['score']->id,
        ]);

        $this->assertSame(0, $summary['locked']);
        $this->assertSame(1, array_sum([
            $summary['synced'],
            $summary['pending'],
            $summary['conflict'],
            $summary['unmapped'],
            $summary['skipped'],
        ]));
    }

    public function test_conventional_publication_locks_parallel_class_structure(): void
    {
        $fixture = $this->mappingFixture();
        $service = app(ParallelCurriculumService::class);

        $this->assertFalse(
            $service->classStructureLocked($fixture['parallelClass'])
        );

        ReportCardPublication::create([
            'tenant_id' => $fixture['tenant']->id,
            'class_arm_id' => $fixture['classArm']->id,
            'term_id' => $fixture['term']->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertTrue(
            $service->classStructureLocked($fixture['parallelClass'])
        );
    }

    public function test_structure_reconciliation_clears_stale_unpublished_derived_score(): void
    {
        $fixture = $this->mappingFixture();

        $summary = app(ParallelCurriculumService::class)
            ->reconcileClassStructure($fixture['parallelClass']);

        $this->assertDatabaseMissing('scores', [
            'id' => $fixture['score']->id,
        ]);

        $this->assertSame(0, $summary['locked']);
        $this->assertSame(1, array_sum([
            $summary['synced'],
            $summary['pending'],
            $summary['conflict'],
            $summary['unmapped'],
            $summary['skipped'],
        ]));
    }

    public function test_destination_subject_must_be_offered_when_master_curriculum_rules_exist(): void
    {
        $fixture = $this->mappingFixture();
        $service = app(ParallelCurriculumService::class);

        $otherSubject = Subject::create([
            'tenant_id' => $fixture['tenant']->id,
            'name' => 'Mathematics',
            'code' => 'MTH',
            'is_active' => true,
        ]);

        ClassLevelSubject::create([
            'tenant_id' => $fixture['tenant']->id,
            'class_level_id' => $fixture['classLevel']->id,
            'academic_track_id' => null,
            'subject_id' => $otherSubject->id,
            'subject_status' => 'compulsory',
            'is_active' => true,
        ]);

        $this->assertFalse(
            $service->conventionalSubjectAvailableForArm(
                $fixture['tenant']->id,
                $fixture['subject']->id,
                $fixture['classArm']
            )
        );
        $this->assertFalse(
            $service->conventionalSubjectAvailableForClassLevel(
                $fixture['tenant']->id,
                $fixture['subject']->id,
                $fixture['classLevel']->load('classArms')
            )
        );

        ClassLevelSubject::create([
            'tenant_id' => $fixture['tenant']->id,
            'class_level_id' => $fixture['classLevel']->id,
            'academic_track_id' => null,
            'subject_id' => $fixture['subject']->id,
            'subject_status' => 'compulsory',
            'is_active' => true,
        ]);

        $this->assertTrue(
            $service->conventionalSubjectAvailableForArm(
                $fixture['tenant']->id,
                $fixture['subject']->id,
                $fixture['classArm']
            )
        );
        $this->assertTrue(
            $service->conventionalSubjectAvailableForClassLevel(
                $fixture['tenant']->id,
                $fixture['subject']->id,
                $fixture['classLevel']->fresh()->load('classArms')
            )
        );
    }

    public function test_parallel_setup_loads_subjects_from_selected_conventional_class_level_even_when_track_rules_differ(): void
    {
        $context = $this->managerContext();

        $level = ClassLevel::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Basic 6',
            'section' => 'primary',
            'order_index' => 6,
        ]);

        $firstTrackId = DB::table('academic_tracks')->insertGetId([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Track One',
            'slug' => 'track-one-'.uniqid(),
            'section' => 'primary',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secondTrackId = DB::table('academic_tracks')->insertGetId([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Track Two',
            'slug' => 'track-two-'.uniqid(),
            'section' => 'primary',
            'is_active' => true,
            'sort_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'class_level_id' => $level->id,
            'academic_track_id' => $secondTrackId,
            'name' => 'A',
        ]);

        $subject = Subject::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Arabic Studies',
            'code' => 'ARB',
            'is_active' => true,
        ]);

        ClassLevelSubject::create([
            'tenant_id' => $context['tenant']->id,
            'class_level_id' => $level->id,
            'academic_track_id' => $firstTrackId,
            'subject_id' => $subject->id,
            'subject_status' => 'compulsory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($context['admin'])
            ->get(route('parallel-curriculum.setup'))
            ->assertOk();

        $compatibility = $response->viewData('integrationSubjectCompatibility');

        $this->assertContains(
            $level->id,
            $compatibility->get($subject->id, [])
        );
    }

    public function test_sync_uses_class_level_mapping_instead_of_rejecting_a_different_arm_track(): void
    {
        $fixture = $this->mappingFixture();

        $trackId = DB::table('academic_tracks')->insertGetId([
            'tenant_id' => $fixture['tenant']->id,
            'name' => 'Science',
            'slug' => 'science-sync-'.uniqid(),
            'section' => 'senior',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ClassLevelSubject::create([
            'tenant_id' => $fixture['tenant']->id,
            'class_level_id' => $fixture['classLevel']->id,
            'academic_track_id' => $trackId,
            'subject_id' => $fixture['subject']->id,
            'subject_status' => 'compulsory',
            'is_active' => true,
        ]);

        $enrolment = ParallelCurriculumEnrolment::where(
                'parallel_curriculum_id',
                $fixture['curriculum']->id
            )
            ->where('student_id', $fixture['student']->id)
            ->firstOrFail();

        $composite = app(ParallelCurriculumService::class)
            ->syncStudent($enrolment, $fixture['term'], true);

        $this->assertNotNull($composite);
        $this->assertNotSame('unmapped', $composite->sync_status);
        $this->assertStringNotContainsString(
            'not offered for this student',
            (string) $composite->sync_message
        );
    }

    public function test_parallel_setup_includes_year_12_subjects_still_recorded_in_class_arm_assignments(): void
    {
        $context = $this->managerContext();

        $year12 = ClassLevel::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Year 12',
            'section' => 'senior',
            'order_index' => 12,
        ]);

        $arm = ClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'class_level_id' => $year12->id,
            'name' => 'A',
        ]);

        $masterSubject = Subject::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'English Language',
            'code' => 'ENG',
            'is_active' => true,
        ]);

        $legacyAssignedSubject = Subject::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Further Mathematics',
            'code' => 'FMTH',
            'is_active' => true,
        ]);

        ClassLevelSubject::create([
            'tenant_id' => $context['tenant']->id,
            'class_level_id' => $year12->id,
            'academic_track_id' => null,
            'subject_id' => $masterSubject->id,
            'subject_status' => 'compulsory',
            'is_active' => true,
        ]);

        DB::table('class_arm_subjects')->insert([
            'tenant_id' => $context['tenant']->id,
            'class_arm_id' => $arm->id,
            'subject_id' => $legacyAssignedSubject->id,
            'teacher_id' => null,
            'session_id' => $context['session']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($context['admin'])
            ->get(route('parallel-curriculum.setup'))
            ->assertOk();

        $compatibility = $response->viewData('integrationSubjectCompatibility');

        $this->assertContains(
            $year12->id,
            $compatibility->get($masterSubject->id, [])
        );
        $this->assertContains(
            $year12->id,
            $compatibility->get($legacyAssignedSubject->id, [])
        );
    }

    public function test_parallel_mapping_form_filters_destination_subjects_by_selected_levels(): void
    {
        $view = file_get_contents(
            resource_path('views/parallel-curriculum/setup.blade.php')
        );

        $this->assertStringContainsString('id="integration-class-levels"', $view);
        $this->assertStringContainsString('id="integration-destination-subject"', $view);
        $this->assertStringNotContainsString(
            'id="integration-destination-subject" required disabled',
            $view
        );
        $this->assertStringContainsString('data-compatible-levels', $view);
        $this->assertStringContainsString(
            "levels?.addEventListener('input', refreshDestinationSubjects);",
            $view
        );
        $this->assertStringContainsString('No conventional subject is offered across every selected class level', $view);
    }

    public function test_parallel_setup_uses_dedicated_controller_action_and_skips_score_workspace_loading(): void
    {
        $route = app('router')->getRoutes()->getByName('parallel-curriculum.setup');

        $this->assertNotNull($route);
        $this->assertStringEndsWith(
            'ParallelCurriculumController@setup',
            $route->getActionName()
        );

        $controller = file_get_contents(
            app_path('Http/Controllers/ParallelCurriculumController.php')
        );

        $this->assertStringContainsString(
            'public function setup(Request $request)',
            $controller
        );
        $this->assertStringContainsString(
            '$workspaces = $isSetup',
            $controller
        );
        $this->assertStringContainsString(
            '? collect()',
            $controller
        );
    }

    public function test_parallel_workspace_paginates_and_server_filters_large_workspace_sets(): void
    {
        $context = $this->managerContext();

        $curriculum = ParallelCurriculum::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Islamiyyah',
            'code' => 'ISL',
            'is_active' => true,
        ]);

        $parallelClass = ParallelCurriculumClass::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Mutawassitah 1',
            'code' => 'M1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ParallelCurriculumClassArm::create([
            'tenant_id' => $context['tenant']->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'name' => 'A',
            'code' => 'A',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        foreach (range(1, 30) as $index) {
            $subject = ParallelCurriculumSubject::create([
                'tenant_id' => $context['tenant']->id,
                'parallel_curriculum_id' => $curriculum->id,
                'name' => 'Parallel Subject '.$index,
                'code' => 'PS'.$index,
                'is_active' => true,
            ]);

            ParallelCurriculumClassSubject::create([
                'tenant_id' => $context['tenant']->id,
                'parallel_curriculum_class_id' => $parallelClass->id,
                'parallel_curriculum_subject_id' => $subject->id,
                'teacher_id' => null,
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($context['admin'])
            ->get(route('parallel-curriculum.index'))
            ->assertOk();

        $paginator = $response->viewData('workspacePaginator');
        $this->assertNotNull($paginator);
        $this->assertSame(30, $paginator->total());
        $this->assertCount(24, $paginator->items());

        $filtered = $this->actingAs($context['admin'])
            ->get(route('parallel-curriculum.index', [
                'workspace_q' => 'Parallel Subject 30',
            ]))
            ->assertOk()
            ->viewData('workspacePaginator');

        $this->assertSame(1, $filtered->total());
        $this->assertSame(
            'Parallel Subject 30',
            $filtered->items()[0]['subject_name']
        );
    }

    public function test_manager_can_create_and_update_multi_level_parallel_mapping(): void
    {
        $context = $this->managerContext();

        $levelOne = ClassLevel::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'SS 1',
            'section' => 'senior',
            'order_index' => 1,
        ]);
        $levelTwo = ClassLevel::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'SS 2',
            'section' => 'senior',
            'order_index' => 2,
        ]);
        $subject = Subject::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Islamiyyah Studies',
            'code' => 'ISS',
            'is_active' => true,
        ]);
        $curriculum = ParallelCurriculum::create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Islamiyyah',
            'code' => 'ISL',
            'is_active' => true,
        ]);

        $payload = [
            '_parallel_section' => 'setup-integration',
            'parallel_curriculum_id' => $curriculum->id,
            'destination_class_level_ids' => [$levelOne->id, $levelTwo->id],
            'destination_subject_id' => $subject->id,
            'minimum_completed_subjects' => 2,
            'require_all_subjects' => 0,
            'auto_sync' => 0,
        ];

        $this->actingAs($context['admin'])
            ->from(route('parallel-curriculum.setup'))
            ->post(route('parallel-curriculum.integrations.store'), $payload)
            ->assertRedirect(route('parallel-curriculum.setup'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('parallel_curriculum_integrations', 2);
        foreach ([$levelOne, $levelTwo] as $level) {
            $this->assertDatabaseHas('parallel_curriculum_integrations', [
                'tenant_id' => $context['tenant']->id,
                'parallel_curriculum_id' => $curriculum->id,
                'destination_class_level_id' => $level->id,
                'destination_subject_id' => $subject->id,
                'minimum_completed_subjects' => 2,
                'require_all_subjects' => 0,
                'auto_sync' => 0,
                'is_active' => 1,
            ]);
        }

        $payload['minimum_completed_subjects'] = 1;
        $payload['require_all_subjects'] = 1;
        $payload['auto_sync'] = 1;

        $this->actingAs($context['admin'])
            ->from(route('parallel-curriculum.setup'))
            ->post(route('parallel-curriculum.integrations.store'), $payload)
            ->assertRedirect(route('parallel-curriculum.setup'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('parallel_curriculum_integrations', 2);
        foreach ([$levelOne, $levelTwo] as $level) {
            $this->assertDatabaseHas('parallel_curriculum_integrations', [
                'destination_class_level_id' => $level->id,
                'minimum_completed_subjects' => 1,
                'require_all_subjects' => 1,
                'auto_sync' => 1,
                'is_active' => 1,
            ]);
        }
    }

    public function test_published_mapping_change_is_rejected_through_web_workflow(): void
    {
        $fixture = $this->mappingFixture();

        ReportCardPublication::create([
            'tenant_id' => $fixture['tenant']->id,
            'class_arm_id' => $fixture['classArm']->id,
            'term_id' => $fixture['term']->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($fixture['admin'])
            ->from(route('parallel-curriculum.setup'))
            ->post(route('parallel-curriculum.integrations.store'), [
                '_parallel_section' => 'setup-integration',
                'parallel_curriculum_id' => $fixture['curriculum']->id,
                'destination_class_level_ids' => [$fixture['classLevel']->id],
                'destination_subject_id' => $fixture['subject']->id,
                'minimum_completed_subjects' => 2,
                'require_all_subjects' => 0,
                'auto_sync' => 1,
            ]);

        $response
            ->assertRedirect(route('parallel-curriculum.setup'))
            ->assertSessionHasErrors('destination_class_level_ids');

        $this->assertSame(
            1,
            $fixture['integration']->fresh()->minimum_completed_subjects
        );
        $this->assertTrue($fixture['integration']->fresh()->require_all_subjects);
    }

    public function test_parallel_mapping_removal_route_is_registered(): void
    {
        $route = app('router')->getRoutes()->getByName('parallel-curriculum.integrations.destroy');

        $this->assertNotNull($route);
        $this->assertSame(['DELETE'], $route->methods());
        $this->assertSame(
            'parallel-curriculum/integrations/{integration}',
            $route->uri()
        );
    }

    public function test_composite_breakdown_requires_parallel_lifecycle_management_access(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ParallelCurriculumController.php')
        );

        $breakdown = strstr(
            $controller,
            'public function breakdown(ParallelCurriculumComposite $composite)'
        );

        $this->assertNotFalse($breakdown);
        $this->assertStringContainsString('$this->assertManage();', $breakdown);
    }

    public function test_parallel_curriculum_workspaces_include_responsive_decongestion_controls(): void
    {
        $index = file_get_contents(
            resource_path('views/parallel-curriculum/index.blade.php')
        );
        $setup = file_get_contents(
            resource_path('views/parallel-curriculum/setup.blade.php')
        );
        $lifecycle = file_get_contents(
            resource_path('views/parallel-curriculum/lifecycle/index.blade.php')
        );
        $operations = file_get_contents(
            resource_path('views/parallel-curriculum/operations.blade.php')
        );
        $results = file_get_contents(
            resource_path('views/parallel-curriculum/results/index.blade.php')
        );
        $ui = file_get_contents(
            resource_path('views/parallel-curriculum/partials/global-ui.blade.php')
        );
        $disclosure = file_get_contents(
            resource_path('views/parallel-curriculum/partials/progressive-disclosure.blade.php')
        );

        $this->assertStringContainsString(
            'id="parallel-workspace-filter-form"',
            $index
        );
        $this->assertStringContainsString(
            '$workspacePaginator',
            $index
        );
        $this->assertStringContainsString(
            '$showQuickWorkspaceSelector',
            $index
        );
        $this->assertStringContainsString(
            'id="parallel-workspaces"',
            $index
        );
        $this->assertStringNotContainsString(
            '1. Create programme',
            $index
        );

        $this->assertStringContainsString(
            'data-storage-key="parallel-config"',
            $setup
        );
        $this->assertStringContainsString(
            'data-setup-group-filter="structure"',
            $setup
        );
        $this->assertStringContainsString(
            'data-setup-group-filter="integration"',
            $setup
        );
        $this->assertStringContainsString(
            '$enrolmentTotal',
            $setup
        );
        $this->assertStringContainsString(
            "parallel-curriculum.partials.progressive-disclosure",
            $setup
        );

        $this->assertStringContainsString(
            'data-storage-key="parallel-lifecycle"',
            $lifecycle
        );
        $this->assertStringContainsString(
            'id="teaching-assignment-model" class="card full" data-collapsible-item',
            $lifecycle
        );
        $this->assertStringContainsString(
            "parallel-curriculum.partials.progressive-disclosure",
            $lifecycle
        );
        $this->assertStringContainsString(
            'data-parallel-validation-target="{{ old(\'_parallel_section\') }}"',
            $lifecycle
        );
        $this->assertStringContainsString(
            'id="lifecycle-promotion-engine"',
            $lifecycle
        );

        $this->assertStringContainsString(
            'data-storage-key="parallel-operations"',
            $operations
        );
        $this->assertStringContainsString('staff-att-table', $operations);
        $this->assertStringContainsString('learner-att-table', $operations);
        $this->assertStringContainsString(
            'data-parallel-validation-target="{{ old(\'_parallel_section\') }}"',
            $operations
        );
        $this->assertStringContainsString(
            'name="_parallel_section" value="parallel-timetable"',
            $operations
        );
        $this->assertStringContainsString(
            "parallel-curriculum.partials.progressive-disclosure",
            $operations
        );

        $this->assertStringContainsString('results-table', $results);
        $this->assertStringContainsString(
            'Parallel result register: card layout on phones.',
            $ui
        );
        $this->assertStringContainsString('aria-expanded', $disclosure);
        $this->assertStringContainsString('localStorage', $disclosure);
        $this->assertStringContainsString('parallelValidationTarget', $disclosure);
        $this->assertStringContainsString('itemContainsValidationError', $disclosure);
        $this->assertStringNotContainsString('hasValidationError', $disclosure);
    }

    public function test_parallel_curriculum_uses_unified_responsive_module_navigation(): void
    {
        $navigation = file_get_contents(
            resource_path('views/parallel-curriculum/partials/module-navigation.blade.php')
        );
        $ui = file_get_contents(
            resource_path('views/parallel-curriculum/partials/global-ui.blade.php')
        );
        $index = file_get_contents(
            resource_path('views/parallel-curriculum/index.blade.php')
        );
        $setup = file_get_contents(
            resource_path('views/parallel-curriculum/setup.blade.php')
        );
        $lifecycle = file_get_contents(
            resource_path('views/parallel-curriculum/lifecycle/index.blade.php')
        );
        $operations = file_get_contents(
            resource_path('views/parallel-curriculum/operations.blade.php')
        );
        $results = file_get_contents(
            resource_path('views/parallel-curriculum/results/index.blade.php')
        );
        $scoreSheet = file_get_contents(
            resource_path('views/parallel-curriculum/score-sheet.blade.php')
        );

        $this->assertStringContainsString('data-pc-module-nav', $navigation);
        $this->assertStringContainsString('data-pc-nav-toggle', $navigation);
        $this->assertStringContainsString('aria-current="page"', $navigation);
        $this->assertStringContainsString('Workspace', $navigation);
        $this->assertStringContainsString('People', $navigation);
        $this->assertStringContainsString('Daily work', $navigation);
        $this->assertStringContainsString('Results', $navigation);

        foreach ([$index, $setup, $lifecycle, $operations, $results, $scoreSheet] as $view) {
            $this->assertStringContainsString(
                "parallel-curriculum.partials.module-navigation",
                $view
            );
        }

        $this->assertStringContainsString(
            'top:calc(var(--header-h, 58px) + 8px)',
            $ui
        );
        $this->assertStringContainsString(
            '@media(max-width:768px)',
            $ui
        );
        $this->assertStringContainsString('pc-module-nav-items', $ui);
        $this->assertStringContainsString('pc-jumpbar', $ui);

        $this->assertStringContainsString(
            'aria-label="Common parallel curriculum tasks"',
            $index
        );
        $this->assertStringContainsString(
            'href="#teaching-assignment-model"',
            $lifecycle
        );
        $this->assertStringContainsString(
            'href="#staff-attendance"',
            $operations
        );
    }

    private function managerContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Parallel Curriculum Test School',
            'slug' => 'parallel-curriculum-test-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $session = AcademicSession::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_current' => true,
        ]);

        $term = Term::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'First Term',
            'is_current' => true,
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Academic Admin',
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        DB::table('school_settings')->insert([
            'tenant_id' => $tenant->id,
            'key' => 'parallel_curriculum_enabled',
            'value' => '1',
            'group' => 'academic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('tenant', 'session', 'term', 'admin');
    }

    private function mappingFixture(): array
    {
        $context = $this->managerContext();
        $tenant = $context['tenant'];
        $session = $context['session'];
        $term = $context['term'];
        $admin = $context['admin'];

        $classLevel = ClassLevel::create([
            'tenant_id' => $tenant->id,
            'name' => 'SS 1',
            'section' => 'senior',
            'order_index' => 1,
        ]);

        $classArm = ClassArm::create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $classLevel->id,
            'name' => 'A',
        ]);

        $subject = Subject::create([
            'tenant_id' => $tenant->id,
            'name' => 'Islamiyyah Studies',
            'code' => 'ISS',
            'is_active' => true,
        ]);

        $student = Student::create([
            'tenant_id' => $tenant->id,
            'current_class_arm_id' => $classArm->id,
            'admission_number' => 'STU-PC-001',
            'first_name' => 'Amina',
            'last_name' => 'Bello',
            'status' => Student::STATUS_ACTIVE,
        ]);

        $curriculum = ParallelCurriculum::create([
            'tenant_id' => $tenant->id,
            'name' => 'Islamiyyah',
            'code' => 'ISL',
            'is_active' => true,
        ]);

        $parallelClass = ParallelCurriculumClass::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Mutawassitah 1',
            'code' => 'M1',
            'is_active' => true,
        ]);

        ParallelCurriculumEnrolment::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'student_id' => $student->id,
            'session_id' => $session->id,
            'is_active' => true,
        ]);

        $integration = ParallelCurriculumIntegration::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'destination_class_level_id' => $classLevel->id,
            'destination_subject_id' => $subject->id,
            'calculation_method' => 'arithmetic_mean',
            'require_all_subjects' => true,
            'minimum_completed_subjects' => 1,
            'auto_sync' => true,
            'is_active' => true,
        ]);

        $composite = ParallelCurriculumComposite::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_integration_id' => $integration->id,
            'parallel_curriculum_class_id' => $parallelClass->id,
            'conventional_class_arm_id' => $classArm->id,
            'student_id' => $student->id,
            'destination_subject_id' => $subject->id,
            'term_id' => $term->id,
            'session_id' => $session->id,
            'average_score' => 80,
            'subject_count' => 4,
            'completed_subject_count' => 4,
            'subject_breakdown' => [],
            'sync_status' => 'synced',
            'sync_message' => 'Composite distributed to the conventional score sheet.',
            'computed_at' => now(),
        ]);

        $assessmentType = $term->assessmentTypes()->firstOrFail();

        $score = Score::create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType->id,
            'term_id' => $term->id,
            'session_id' => $session->id,
            'score' => 24,
            'entered_at' => now(),
            'score_source' => ParallelCurriculumService::SCORE_SOURCE,
            'source_reference_type' => ParallelCurriculumService::SOURCE_REFERENCE_TYPE,
            'source_reference_id' => $composite->id,
            'is_source_locked' => true,
            'source_synced_at' => now(),
        ]);

        return compact(
            'tenant',
            'session',
            'term',
            'admin',
            'classLevel',
            'classArm',
            'subject',
            'student',
            'curriculum',
            'parallelClass',
            'integration',
            'composite',
            'score'
        );
    }
}
