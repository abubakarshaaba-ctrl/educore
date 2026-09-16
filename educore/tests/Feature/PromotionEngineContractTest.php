<?php

namespace Tests\Feature;

use App\Http\Controllers\ClassController;
use App\Models\GradingSystem;
use App\Models\PromotionRule;
use App\Models\StudentEnrollment;
use App\Models\TermlySummary;
use Illuminate\Http\Request;
use Tests\Feature\Concerns\BuildsAcademicCycleTestSchema;
use Tests\TestCase;

class PromotionEngineContractTest extends TestCase
{
    use BuildsAcademicCycleTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildAcademicCycleSchema();
    }

    public function test_manual_bulk_promotion_uses_minimum_average_without_requiring_student_ids(): void
    {
        [$tenant, $actor, $session, $term, $sourceArm, $targetArm, $student] = $this->promotionFixture();
        $this->actingAs($actor);

        $request = Request::create('/classes/bulk-promote', 'POST', [
            'from_class_arm_id' => $sourceArm->id,
            'to_class_arm_id' => $targetArm->id,
            'term_id' => $term->id,
            'min_average' => 45,
        ]);
        $request->setUserResolver(fn () => $actor);

        app(ClassController::class)->bulkPromote($request);

        $this->assertSame($targetArm->id, $student->fresh()->current_class_arm_id);
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->where('is_current', true)->count());
        $this->assertSame(StudentEnrollment::STATUS_CLOSED, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->where('class_arm_id', $sourceArm->id)->first()->status);
        $this->assertSame('promoted', TermlySummary::withoutTenantScope()->where('student_id', $student->id)->where('term_id', $term->id)->value('promotion_status'));
    }

    public function test_repeating_the_same_manual_promotion_does_not_create_duplicate_current_enrolments(): void
    {
        [$tenant, $actor, $session, $term, $sourceArm, $targetArm, $student] = $this->promotionFixture();
        $this->actingAs($actor);

        $payload = [
            'operations' => [[
                'from_class_arm_id' => $sourceArm->id,
                'to_class_arm_id' => $targetArm->id,
                'term_id' => $term->id,
                'min_average' => 45,
            ]],
        ];

        foreach ([1, 2] as $attempt) {
            $request = Request::create('/classes/bulk-promote', 'POST', $payload);
            $request->setUserResolver(fn () => $actor);
            app(ClassController::class)->bulkPromote($request);
        }

        $this->assertSame(2, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->count());
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->where('is_current', true)->count());
        $this->assertSame($targetArm->id, $student->fresh()->current_class_arm_id);
    }

    public function test_students_below_manual_threshold_remain_in_the_source_class(): void
    {
        [$tenant, $actor, $session, $term, $sourceArm, $targetArm, $student] = $this->promotionFixture();
        $this->actingAs($actor);

        $request = Request::create('/classes/bulk-promote', 'POST', [
            'operations' => [[
                'from_class_arm_id' => $sourceArm->id,
                'to_class_arm_id' => $targetArm->id,
                'term_id' => $term->id,
                'min_average' => 80,
            ]],
        ]);
        $request->setUserResolver(fn () => $actor);

        app(ClassController::class)->bulkPromote($request);

        $this->assertSame($sourceArm->id, $student->fresh()->current_class_arm_id);
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->where('is_current', true)->count());
    }

    public function test_multiple_class_operations_are_processed_in_one_request(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2025/2026', true);
        $term = $this->termFixture($tenant, $session, 'Third Term', true);

        $sourceOne = $this->classArmFixture($tenant, 'Primary 1', 1, 'A');
        $targetOne = $this->classArmFixture($tenant, 'Primary 2', 2, 'A');
        $sourceTwo = $this->classArmFixture($tenant, 'Primary 3', 3, 'A');
        $targetTwo = $this->classArmFixture($tenant, 'Primary 4', 4, 'A');

        $studentOne = $this->studentFixture($tenant, $sourceOne, ['first_name' => 'Ada', 'admission_number' => 'M001']);
        $studentTwo = $this->studentFixture($tenant, $sourceTwo, ['first_name' => 'Bola', 'admission_number' => 'M002']);
        $this->enrollmentFixture($tenant, $studentOne, $sourceOne, $session, $term);
        $this->enrollmentFixture($tenant, $studentTwo, $sourceTwo, $session, $term);
        $this->summaryFixture($tenant, $studentOne, $sourceOne, $term, 'pending');
        $this->summaryFixture($tenant, $studentTwo, $sourceTwo, $term, 'pending');

        $this->actingAs($actor);
        $request = Request::create('/classes/bulk-promote', 'POST', [
            'operations' => [
                ['from_class_arm_id' => $sourceOne->id, 'to_class_arm_id' => $targetOne->id, 'term_id' => $term->id, 'min_average' => 45],
                ['from_class_arm_id' => $sourceTwo->id, 'to_class_arm_id' => $targetTwo->id, 'term_id' => $term->id, 'min_average' => 45],
            ],
        ]);
        $request->setUserResolver(fn () => $actor);

        app(ClassController::class)->bulkPromote($request);

        $this->assertSame($targetOne->id, $studentOne->fresh()->current_class_arm_id);
        $this->assertSame($targetTwo->id, $studentTwo->fresh()->current_class_arm_id);
    }

    public function test_invalid_skipped_level_destination_is_blocked_without_moving_student(): void
    {
        [$tenant, $actor, $session, $term, $sourceArm, $targetArm, $student] = $this->promotionFixture();
        $skippedTarget = $this->classArmFixture($tenant, 'Primary 3', 3, 'A');
        $this->actingAs($actor);

        $request = Request::create('/classes/bulk-promote', 'POST', [
            'operations' => [[
                'from_class_arm_id' => $sourceArm->id,
                'to_class_arm_id' => $skippedTarget->id,
                'term_id' => $term->id,
                'min_average' => 45,
            ]],
        ]);
        $request->setUserResolver(fn () => $actor);

        app(ClassController::class)->bulkPromote($request);

        $this->assertSame($sourceArm->id, $student->fresh()->current_class_arm_id);
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->where('is_current', true)->count());
    }

    public function test_terminal_level_is_not_manually_promoted_to_an_arbitrary_class(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2025/2026', true);
        $term = $this->termFixture($tenant, $session, 'Third Term', true);
        $lowerArm = $this->classArmFixture($tenant, 'Primary 5', 5, 'A');
        $terminalArm = $this->classArmFixture($tenant, 'Primary 6', 6, 'A');
        $student = $this->studentFixture($tenant, $terminalArm);
        $this->enrollmentFixture($tenant, $student, $terminalArm, $session, $term);
        $this->summaryFixture($tenant, $student, $terminalArm, $term, 'pending');
        $this->actingAs($actor);

        $request = Request::create('/classes/bulk-promote', 'POST', [
            'operations' => [[
                'from_class_arm_id' => $terminalArm->id,
                'to_class_arm_id' => $lowerArm->id,
                'term_id' => $term->id,
                'min_average' => 45,
            ]],
        ]);
        $request->setUserResolver(fn () => $actor);

        app(ClassController::class)->bulkPromote($request);

        $this->assertSame($terminalArm->id, $student->fresh()->current_class_arm_id);
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->where('is_current', true)->count());
    }

    public function test_grade_scale_can_be_applied_to_multiple_class_levels_atomically(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $levelOne = $this->classArmFixture($tenant, 'JSS 1', 1, 'A')->classLevel;
        $levelTwo = $this->classArmFixture($tenant, 'JSS 2', 2, 'A')->classLevel;
        $this->actingAs($actor);

        $request = Request::create('/classes/grading', 'POST', [
            'class_level_ids' => [$levelOne->id, $levelTwo->id],
            'grade_letter' => 'F',
            'min_score' => 0,
            'max_score' => 49,
            'remark' => 'Fail',
            'grade_point' => 0,
            'is_pass_grade' => 0,
        ]);
        $request->setUserResolver(fn () => $actor);

        app(ClassController::class)->storeGrade($request);

        $this->assertSame(2, GradingSystem::withoutTenantScope()->whereIn('class_level_id', [$levelOne->id, $levelTwo->id])->where('grade_letter', 'F')->count());
    }

    public function test_promotion_rule_can_be_applied_to_multiple_class_levels(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $levelOne = $this->classArmFixture($tenant, 'JSS 1', 1, 'A')->classLevel;
        $levelTwo = $this->classArmFixture($tenant, 'JSS 2', 2, 'A')->classLevel;
        $this->actingAs($actor);

        $request = Request::create('/classes/promotion', 'POST', [
            'class_level_ids' => [$levelOne->id, $levelTwo->id],
            'min_required_average' => 50,
            'max_failed_subjects_allowed' => 2,
            'compulsory_subject_ids' => [],
        ]);
        $request->setUserResolver(fn () => $actor);

        app(ClassController::class)->savePromotion($request);

        $this->assertSame(2, PromotionRule::withoutTenantScope()->whereIn('class_level_id', [$levelOne->id, $levelTwo->id])->count());
    }

    public function test_manual_bulk_promotion_form_stays_aligned_with_controller_contract(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ClassController.php'));
        $view = file_get_contents(resource_path('views/classes/bulk-promote.blade.php'));

        $this->assertStringContainsString("'operations.*.min_average'", $controller);
        $this->assertStringContainsString("'operations.*.student_ids'       => ['nullable','array']", $controller);
        $this->assertStringContainsString('name="operations[{{ $i }}][min_average]"', $view);
        $this->assertStringContainsString('name="preview_only"', $view);
    }

    private function promotionFixture(): array
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2025/2026', true);
        $term = $this->termFixture($tenant, $session, 'Third Term', true);
        $sourceArm = $this->classArmFixture($tenant, 'Primary 1', 1, 'A');
        $targetArm = $this->classArmFixture($tenant, 'Primary 2', 2, 'A');
        $student = $this->studentFixture($tenant, $sourceArm);
        $this->enrollmentFixture($tenant, $student, $sourceArm, $session, $term);
        $this->summaryFixture($tenant, $student, $sourceArm, $term, 'pending');

        return [$tenant, $actor, $session, $term, $sourceArm, $targetArm, $student];
    }
}
