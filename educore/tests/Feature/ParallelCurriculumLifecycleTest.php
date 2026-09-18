<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ClassLevelSubject;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumComposite;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumIntegration;
use App\Models\ReportCardPublication;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Services\ParallelCurriculumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function mappingFixture(): array
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
