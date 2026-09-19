<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AssessmentTemplate;
use App\Models\AssessmentTemplateComponent;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumClassGrade;
use App\Models\ParallelCurriculumClassSubject;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\ParallelCurriculumPromotion;
use App\Models\ParallelCurriculumPromotionRule;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\ParallelCurriculumScore;
use App\Models\ParallelCurriculumSubject;
use App\Models\ParallelCurriculumTransfer;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Term;
use App\Services\ParallelCurriculumLifecycleService;
use App\Services\ParallelCurriculumResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ParallelCurriculumAcademicLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_arm_capacity_cannot_be_reduced_below_operational_session_load(): void
    {
        $f = $this->fixture();
        $service = app(ParallelCurriculumLifecycleService::class);

        $this->assertSame(1, $service->armOperationalLoad($f['sourceArmA']));

        $this->expectException(ValidationException::class);
        $service->assertArmCapacityChange($f['sourceArmA'], 0);
    }

    public function test_future_session_placement_prevents_arm_archive(): void
    {
        $f = $this->fixture();

        ParallelCurriculumEnrolment::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'parallel_curriculum_class_arm_id' => $f['sourceArmB']->id,
            'student_id' => $f['student']->id,
            'session_id' => $f['targetSession']->id,
            'is_active' => true,
        ]);

        $this->assertTrue(
            app(ParallelCurriculumLifecycleService::class)
                ->armHasOperationalPlacements($f['sourceArmB'])
        );
    }

    public function test_intra_class_transfer_changes_arm_and_records_history(): void
    {
        $f = $this->fixture();

        $transfer = app(ParallelCurriculumLifecycleService::class)->transfer(
            $f['enrolment'],
            $f['sourceClass'],
            $f['sourceArmB'],
            'Administrative arm balancing.',
            now()->toDateString(),
            null
        );

        $this->assertSame(
            ParallelCurriculumTransfer::TYPE_INTRA_CLASS,
            $transfer->movement_type
        );

        $enrolment = $f['enrolment']->fresh();
        $this->assertSame($f['sourceClass']->id, $enrolment->parallel_curriculum_class_id);
        $this->assertSame($f['sourceArmB']->id, $enrolment->parallel_curriculum_class_arm_id);

        $this->assertDatabaseHas('parallel_curriculum_transfers', [
            'student_id' => $f['student']->id,
            'from_arm_id' => $f['sourceArmA']->id,
            'to_arm_id' => $f['sourceArmB']->id,
            'movement_type' => ParallelCurriculumTransfer::TYPE_INTRA_CLASS,
            'status' => 'completed',
        ]);
    }

    public function test_inter_class_transfer_is_blocked_after_source_scores_exist(): void
    {
        $f = $this->fixture();

        $this->expectException(ValidationException::class);

        app(ParallelCurriculumLifecycleService::class)->transfer(
            $f['enrolment'],
            $f['destinationClass'],
            $f['destinationArmA'],
            'Attempted level movement after score entry.',
            now()->toDateString(),
            null
        );
    }

    public function test_class_specific_grade_system_overrides_programme_default(): void
    {
        $f = $this->fixture();

        ParallelCurriculumClassGrade::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'grade_letter' => 'DIST',
            'min_score' => 0,
            'max_score' => 100,
            'remark' => 'Class override',
            'is_pass_grade' => true,
            'grade_point' => 5,
        ]);

        $report = app(ParallelCurriculumResultService::class)->studentReport(
            $f['sourceClass'],
            $f['term'],
            $f['student']->id
        );

        $this->assertNotNull($report);
        $this->assertSame('class', $report['grading_source']);
        $this->assertSame(
            'DIST',
            $report['student_result']['subjects']->first()['grade']
        );
        $this->assertSame(
            'Class override',
            $report['student_result']['subjects']->first()['remark']
        );
    }

    public function test_promotion_requires_published_source_result_even_when_complete_result_is_optional(): void
    {
        $f = $this->fixture();

        ParallelCurriculumClassGrade::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'grade_letter' => 'P',
            'min_score' => 0,
            'max_score' => 100,
            'remark' => 'Pass',
            'is_pass_grade' => true,
        ]);

        ParallelCurriculumPromotionRule::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'source_class_id' => $f['sourceClass']->id,
            'destination_class_id' => $f['destinationClass']->id,
            'minimum_average' => 50,
            'max_failed_subjects' => 0,
            'require_complete_result' => false,
            'failure_action' => 'repeat',
            'arm_strategy' => 'same_name',
            'is_terminal' => false,
            'is_active' => true,
        ]);

        $preview = app(ParallelCurriculumLifecycleService::class)->promotionPreview(
            $f['curriculum'],
            $f['sourceSession'],
            $f['targetSession']
        );

        $this->assertSame(1, $preview['counts']['blocked']);
        $this->assertSame('blocked', $preview['rows']->first()['decision']);
        $this->assertStringContainsString(
            'Publish the final parallel result',
            $preview['rows']->first()['reason']
        );
    }

    public function test_promotion_preview_does_not_overwrite_existing_target_session_placement(): void
    {
        $f = $this->fixture();

        ParallelCurriculumClassGrade::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'grade_letter' => 'P',
            'min_score' => 0,
            'max_score' => 100,
            'remark' => 'Pass',
            'is_pass_grade' => true,
        ]);

        ParallelCurriculumReportPublication::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'term_id' => $f['term']->id,
            'status' => ParallelCurriculumReportPublication::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        ParallelCurriculumPromotionRule::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'source_class_id' => $f['sourceClass']->id,
            'destination_class_id' => $f['destinationClass']->id,
            'minimum_average' => 50,
            'max_failed_subjects' => 0,
            'require_complete_result' => true,
            'failure_action' => 'repeat',
            'arm_strategy' => 'same_name',
            'is_terminal' => false,
            'is_active' => true,
        ]);

        ParallelCurriculumEnrolment::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'parallel_curriculum_class_arm_id' => $f['sourceArmB']->id,
            'student_id' => $f['student']->id,
            'session_id' => $f['targetSession']->id,
            'is_active' => true,
        ]);

        $preview = app(ParallelCurriculumLifecycleService::class)->promotionPreview(
            $f['curriculum'],
            $f['sourceSession'],
            $f['targetSession']
        );

        $this->assertSame(1, $preview['counts']['blocked']);
        $this->assertStringContainsString(
            'placement already exists',
            $preview['rows']->first()['reason']
        );

        $this->expectException(ValidationException::class);

        app(ParallelCurriculumLifecycleService::class)->executePromotion(
            $f['curriculum'],
            $f['sourceSession'],
            $f['targetSession'],
            null
        );
    }

    public function test_promotion_preview_reserves_arm_capacity_across_the_batch(): void
    {
        $f = $this->fixture();

        $f['destinationArmA']->update(['capacity' => 1]);

        $secondStudent = Student::create([
            'tenant_id' => $f['tenant']->id,
            'current_class_arm_id' => $f['conventionalArm']->id,
            'admission_number' => 'PC-LIFE-002',
            'first_name' => 'Musa',
            'last_name' => 'Aliyu',
            'status' => Student::STATUS_ACTIVE,
        ]);

        ParallelCurriculumEnrolment::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'parallel_curriculum_class_arm_id' => $f['sourceArmB']->id,
            'student_id' => $secondStudent->id,
            'session_id' => $f['sourceSession']->id,
            'is_active' => true,
        ]);

        foreach ([[$f['ca'], 35], [$f['exam'], 50]] as [$component, $score]) {
            ParallelCurriculumScore::create([
                'tenant_id' => $f['tenant']->id,
                'parallel_curriculum_id' => $f['curriculum']->id,
                'parallel_curriculum_class_id' => $f['sourceClass']->id,
                'student_id' => $secondStudent->id,
                'parallel_curriculum_subject_id' => $f['subject']->id,
                'assessment_template_component_id' => $component->id,
                'term_id' => $f['term']->id,
                'session_id' => $f['sourceSession']->id,
                'score' => $score,
                'entered_at' => now(),
            ]);
        }

        ParallelCurriculumClassGrade::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'grade_letter' => 'P',
            'min_score' => 0,
            'max_score' => 100,
            'remark' => 'Pass',
            'is_pass_grade' => true,
        ]);

        ParallelCurriculumReportPublication::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'term_id' => $f['term']->id,
            'status' => ParallelCurriculumReportPublication::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        ParallelCurriculumPromotionRule::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'source_class_id' => $f['sourceClass']->id,
            'destination_class_id' => $f['destinationClass']->id,
            'minimum_average' => 50,
            'max_failed_subjects' => 0,
            'require_complete_result' => true,
            'failure_action' => 'repeat',
            'arm_strategy' => 'first_available',
            'is_terminal' => false,
            'is_active' => true,
        ]);

        $preview = app(ParallelCurriculumLifecycleService::class)->promotionPreview(
            $f['curriculum'],
            $f['sourceSession'],
            $f['targetSession']
        );

        $this->assertSame(2, $preview['counts']['total']);
        $this->assertSame(1, $preview['counts']['promoted']);
        $this->assertSame(1, $preview['counts']['blocked']);
    }

    public function test_promotion_creates_next_session_placement_and_preserves_source_session(): void
    {
        $f = $this->fixture();

        ParallelCurriculumClassGrade::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'grade_letter' => 'P',
            'min_score' => 0,
            'max_score' => 100,
            'remark' => 'Pass',
            'is_pass_grade' => true,
        ]);

        ParallelCurriculumReportPublication::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'term_id' => $f['term']->id,
            'status' => ParallelCurriculumReportPublication::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        ParallelCurriculumPromotionRule::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'source_class_id' => $f['sourceClass']->id,
            'destination_class_id' => $f['destinationClass']->id,
            'minimum_average' => 50,
            'max_failed_subjects' => 0,
            'require_complete_result' => true,
            'failure_action' => 'repeat',
            'arm_strategy' => 'same_name',
            'is_terminal' => false,
            'is_active' => true,
        ]);

        $service = app(ParallelCurriculumLifecycleService::class);

        $preview = $service->promotionPreview(
            $f['curriculum'],
            $f['sourceSession'],
            $f['targetSession']
        );

        $this->assertSame(1, $preview['counts']['promoted']);
        $this->assertSame(0, $preview['counts']['blocked']);

        $result = $service->executePromotion(
            $f['curriculum'],
            $f['sourceSession'],
            $f['targetSession'],
            null
        );

        $this->assertSame(1, $result['created']);

        $this->assertDatabaseHas('parallel_curriculum_enrolments', [
            'id' => $f['enrolment']->id,
            'session_id' => $f['sourceSession']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'parallel_curriculum_class_arm_id' => $f['sourceArmA']->id,
        ]);

        $this->assertDatabaseHas('parallel_curriculum_enrolments', [
            'student_id' => $f['student']->id,
            'session_id' => $f['targetSession']->id,
            'parallel_curriculum_class_id' => $f['destinationClass']->id,
            'parallel_curriculum_class_arm_id' => $f['destinationArmA']->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('parallel_curriculum_promotions', [
            'student_id' => $f['student']->id,
            'source_enrolment_id' => $f['enrolment']->id,
            'target_session_id' => $f['targetSession']->id,
            'decision' => ParallelCurriculumPromotion::DECISION_PROMOTED,
        ]);
    }

    public function test_failed_promotion_repeats_learner_in_same_parallel_class_next_session(): void
    {
        $f = $this->fixture();

        ParallelCurriculumClassGrade::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'grade_letter' => 'P',
            'min_score' => 0,
            'max_score' => 100,
            'remark' => 'Pass',
            'is_pass_grade' => true,
        ]);

        ParallelCurriculumReportPublication::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'term_id' => $f['term']->id,
            'status' => ParallelCurriculumReportPublication::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        ParallelCurriculumPromotionRule::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'source_class_id' => $f['sourceClass']->id,
            'destination_class_id' => $f['destinationClass']->id,
            'minimum_average' => 90,
            'max_failed_subjects' => 0,
            'require_complete_result' => true,
            'failure_action' => ParallelCurriculumPromotionRule::FAILURE_REPEAT,
            'arm_strategy' => 'same_name',
            'is_terminal' => false,
            'is_active' => true,
        ]);

        $service = app(ParallelCurriculumLifecycleService::class);
        $preview = $service->promotionPreview(
            $f['curriculum'],
            $f['sourceSession'],
            $f['targetSession']
        );

        $this->assertSame(1, $preview['counts']['repeat']);
        $this->assertSame(
            ParallelCurriculumPromotion::DECISION_REPEAT,
            $preview['rows']->first()['decision']
        );
        $this->assertSame(
            $f['sourceClass']->id,
            $preview['rows']->first()['destination_class']->id
        );
        $this->assertSame(
            $f['sourceArmA']->id,
            $preview['rows']->first()['destination_arm']->id
        );

        $result = $service->executePromotion(
            $f['curriculum'],
            $f['sourceSession'],
            $f['targetSession'],
            null
        );

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('parallel_curriculum_enrolments', [
            'student_id' => $f['student']->id,
            'session_id' => $f['targetSession']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'parallel_curriculum_class_arm_id' => $f['sourceArmA']->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('parallel_curriculum_promotions', [
            'student_id' => $f['student']->id,
            'source_enrolment_id' => $f['enrolment']->id,
            'target_session_id' => $f['targetSession']->id,
            'decision' => ParallelCurriculumPromotion::DECISION_REPEAT,
        ]);
    }

    public function test_terminal_parallel_class_graduates_without_creating_target_placement(): void
    {
        $f = $this->fixture();

        ParallelCurriculumClassGrade::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'grade_letter' => 'P',
            'min_score' => 0,
            'max_score' => 100,
            'remark' => 'Pass',
            'is_pass_grade' => true,
        ]);

        ParallelCurriculumReportPublication::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'parallel_curriculum_class_id' => $f['sourceClass']->id,
            'term_id' => $f['term']->id,
            'status' => ParallelCurriculumReportPublication::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        ParallelCurriculumPromotionRule::create([
            'tenant_id' => $f['tenant']->id,
            'parallel_curriculum_id' => $f['curriculum']->id,
            'source_class_id' => $f['sourceClass']->id,
            'destination_class_id' => null,
            'minimum_average' => 50,
            'max_failed_subjects' => 0,
            'require_complete_result' => true,
            'failure_action' => ParallelCurriculumPromotionRule::FAILURE_REPEAT,
            'arm_strategy' => 'same_name',
            'is_terminal' => true,
            'is_active' => true,
        ]);

        $service = app(ParallelCurriculumLifecycleService::class);
        $preview = $service->promotionPreview(
            $f['curriculum'],
            $f['sourceSession'],
            $f['targetSession']
        );

        $this->assertSame(1, $preview['counts']['graduated']);
        $this->assertSame(
            ParallelCurriculumPromotion::DECISION_GRADUATED,
            $preview['rows']->first()['decision']
        );
        $this->assertNull($preview['rows']->first()['destination_class']);
        $this->assertNull($preview['rows']->first()['destination_arm']);

        $result = $service->executePromotion(
            $f['curriculum'],
            $f['sourceSession'],
            $f['targetSession'],
            null
        );

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['graduated']);
        $this->assertDatabaseMissing('parallel_curriculum_enrolments', [
            'student_id' => $f['student']->id,
            'session_id' => $f['targetSession']->id,
        ]);
        $this->assertDatabaseHas('parallel_curriculum_promotions', [
            'student_id' => $f['student']->id,
            'source_enrolment_id' => $f['enrolment']->id,
            'target_session_id' => $f['targetSession']->id,
            'decision' => ParallelCurriculumPromotion::DECISION_GRADUATED,
        ]);
    }

    private function fixture(): array
    {
        $tenant = Tenant::create([
            'name' => 'Parallel Lifecycle School',
            'slug' => 'parallel-lifecycle-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $sourceSession = AcademicSession::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_current' => true,
        ]);

        $targetSession = AcademicSession::create([
            'tenant_id' => $tenant->id,
            'name' => '2027/2028',
            'is_current' => false,
        ]);

        $term = Term::create([
            'tenant_id' => $tenant->id,
            'session_id' => $sourceSession->id,
            'name' => 'Third Term',
            'is_current' => true,
        ]);

        $conventionalLevel = ClassLevel::create([
            'tenant_id' => $tenant->id,
            'name' => 'JSS 1',
            'section' => 'junior',
            'order_index' => 1,
        ]);

        $conventionalArm = ClassArm::create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $conventionalLevel->id,
            'name' => 'A',
        ]);

        $student = Student::create([
            'tenant_id' => $tenant->id,
            'current_class_arm_id' => $conventionalArm->id,
            'admission_number' => 'PC-LIFE-001',
            'first_name' => 'Amina',
            'last_name' => 'Bello',
            'status' => Student::STATUS_ACTIVE,
        ]);

        $template = AssessmentTemplate::create([
            'tenant_id' => $tenant->id,
            'name' => 'Parallel 40/60',
            'status' => AssessmentTemplate::STATUS_ACTIVE,
        ]);

        $ca = AssessmentTemplateComponent::create([
            'tenant_id' => $tenant->id,
            'assessment_template_id' => $template->id,
            'name' => 'CA',
            'weight_percentage' => 40,
            'component_type' => 'continuous_assessment',
            'entry_mode' => 'manual',
            'sort_order' => 1,
        ]);

        $exam = AssessmentTemplateComponent::create([
            'tenant_id' => $tenant->id,
            'assessment_template_id' => $template->id,
            'name' => 'Exam',
            'weight_percentage' => 60,
            'component_type' => 'exam',
            'entry_mode' => 'manual',
            'sort_order' => 2,
        ]);

        $curriculum = ParallelCurriculum::create([
            'tenant_id' => $tenant->id,
            'name' => 'Islamiyyah',
            'code' => 'ISL',
            'default_assessment_template_id' => $template->id,
            'is_active' => true,
        ]);

        $subject = ParallelCurriculumSubject::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Qur\'an',
            'code' => 'QRN',
            'is_active' => true,
        ]);

        $sourceClass = ParallelCurriculumClass::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Mutawassitah 1',
            'code' => 'M1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $destinationClass = ParallelCurriculumClass::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'name' => 'Mutawassitah 2',
            'code' => 'M2',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $sourceArmA = ParallelCurriculumClassArm::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $sourceClass->id,
            'name' => 'A',
            'code' => 'A',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $sourceArmB = ParallelCurriculumClassArm::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $sourceClass->id,
            'name' => 'B',
            'code' => 'B',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $destinationArmA = ParallelCurriculumClassArm::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $destinationClass->id,
            'name' => 'A',
            'code' => 'A',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ParallelCurriculumClassSubject::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_class_id' => $sourceClass->id,
            'parallel_curriculum_subject_id' => $subject->id,
            'is_active' => true,
        ]);

        $enrolment = ParallelCurriculumEnrolment::create([
            'tenant_id' => $tenant->id,
            'parallel_curriculum_id' => $curriculum->id,
            'parallel_curriculum_class_id' => $sourceClass->id,
            'parallel_curriculum_class_arm_id' => $sourceArmA->id,
            'student_id' => $student->id,
            'session_id' => $sourceSession->id,
            'is_active' => true,
        ]);

        foreach ([[$ca, 35], [$exam, 50]] as [$component, $score]) {
            ParallelCurriculumScore::create([
                'tenant_id' => $tenant->id,
                'parallel_curriculum_id' => $curriculum->id,
                'parallel_curriculum_class_id' => $sourceClass->id,
                'student_id' => $student->id,
                'parallel_curriculum_subject_id' => $subject->id,
                'assessment_template_component_id' => $component->id,
                'term_id' => $term->id,
                'session_id' => $sourceSession->id,
                'score' => $score,
                'entered_at' => now(),
            ]);
        }

        return compact(
            'tenant',
            'sourceSession',
            'targetSession',
            'term',
            'conventionalLevel',
            'conventionalArm',
            'student',
            'template',
            'ca',
            'exam',
            'curriculum',
            'subject',
            'sourceClass',
            'destinationClass',
            'sourceArmA',
            'sourceArmB',
            'destinationArmA',
            'enrolment'
        );
    }
}
