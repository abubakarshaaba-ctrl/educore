<?php

namespace Tests\Feature;

use App\Models\Score;
use App\Models\Subject;
use App\Models\TermlySummary;
use App\Services\ReportCardComputationService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsAcademicCycleTestSchema;
use Tests\TestCase;

class MobileReportComputationTest extends TestCase
{
    use BuildsAcademicCycleTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildAcademicCycleSchema();
    }

    public function test_computation_is_tenant_scoped_ranks_students_and_preserves_human_remarks(): void
    {
        $tenant = $this->tenantFixture(['slug' => 'report-compute-local']);
        $session = $this->sessionFixture($tenant, '2026/2027', true);
        $term = $this->termFixture($tenant, $session, 'First Term', true);
        $classArm = $this->classArmFixture($tenant, 'Year 12', 12, 'A');
        $first = $this->studentFixture($tenant, $classArm, ['admission_number' => 'CMP001', 'first_name' => 'Amina']);
        $second = $this->studentFixture($tenant, $classArm, ['admission_number' => 'CMP002', 'first_name' => 'Bola']);

        $math = Subject::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Mathematics',
            'code' => 'MTH',
            'is_active' => true,
        ]);
        $biology = Subject::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Biology',
            'code' => 'BIO',
            'is_active' => true,
        ]);

        $this->score($tenant->id, $first->id, $math->id, $term->id, $session->id, $classArm->id, 80);
        $this->score($tenant->id, $first->id, $biology->id, $term->id, $session->id, $classArm->id, 70);
        $this->score($tenant->id, $second->id, $math->id, $term->id, $session->id, $classArm->id, 60);
        $this->score($tenant->id, $second->id, $biology->id, $term->id, $session->id, $classArm->id, 50);

        TermlySummary::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'student_id' => $first->id,
            'class_arm_id' => $classArm->id,
            'term_id' => $term->id,
            'session_id' => $session->id,
            'final_average' => 1,
            'form_tutor_remark' => 'A carefully written form tutor remark.',
            'principal_remark' => 'A carefully written principal remark.',
        ]);

        $foreign = $this->tenantFixture(['slug' => 'report-compute-foreign']);
        $foreignSession = $this->sessionFixture($foreign, '2026/2027', true);
        $foreignTerm = $this->termFixture($foreign, $foreignSession, 'First Term', true);
        $foreignClass = $this->classArmFixture($foreign, 'Year 12', 12, 'A');
        $foreignStudent = $this->studentFixture($foreign, $foreignClass, ['admission_number' => 'FOREIGN-CMP']);
        $foreignSubject = Subject::withoutTenantScope()->create([
            'tenant_id' => $foreign->id,
            'name' => 'Foreign Subject',
            'code' => 'FOR',
            'is_active' => true,
        ]);
        $this->score($foreign->id, $foreignStudent->id, $foreignSubject->id, $foreignTerm->id, $foreignSession->id, $foreignClass->id, 99);

        $computed = app(ReportCardComputationService::class)->compute($tenant->id, $classArm->id, $term->id);
        $this->assertSame(2, $computed);

        $firstSummary = TermlySummary::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('student_id', $first->id)
            ->where('term_id', $term->id)
            ->firstOrFail();
        $secondSummary = TermlySummary::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('student_id', $second->id)
            ->where('term_id', $term->id)
            ->firstOrFail();

        $this->assertSame(75.0, (float) $firstSummary->final_average);
        $this->assertSame(55.0, (float) $secondSummary->final_average);
        $this->assertSame(1, (int) $firstSummary->position_in_class);
        $this->assertSame(2, (int) $secondSummary->position_in_class);
        $this->assertSame(2, (int) $firstSummary->total_students_in_class);
        $this->assertSame(2, (int) $firstSummary->subjects_offered);
        $this->assertSame(75.0, (float) $firstSummary->class_highest_avg);
        $this->assertSame(55.0, (float) $firstSummary->class_lowest_avg);
        $this->assertSame('A carefully written form tutor remark.', $firstSummary->form_tutor_remark);
        $this->assertSame('A carefully written principal remark.', $firstSummary->principal_remark);
        $this->assertNotNull($firstSummary->computed_at);
        $this->assertCount(2, $firstSummary->subject_breakdown);

        $this->assertDatabaseMissing('termly_summaries', [
            'tenant_id' => $tenant->id,
            'student_id' => $foreignStudent->id,
        ]);
    }

    public function test_equal_averages_receive_equal_class_position(): void
    {
        $tenant = $this->tenantFixture(['slug' => 'report-compute-ties']);
        $session = $this->sessionFixture($tenant, '2026/2027', true);
        $term = $this->termFixture($tenant, $session, 'First Term', true);
        $classArm = $this->classArmFixture($tenant, 'Year 11', 11, 'A');
        $first = $this->studentFixture($tenant, $classArm, ['admission_number' => 'TIE001']);
        $second = $this->studentFixture($tenant, $classArm, ['admission_number' => 'TIE002']);
        $subject = Subject::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'name' => 'English',
            'code' => 'ENG',
            'is_active' => true,
        ]);
        $this->score($tenant->id, $first->id, $subject->id, $term->id, $session->id, $classArm->id, 70);
        $this->score($tenant->id, $second->id, $subject->id, $term->id, $session->id, $classArm->id, 70);

        app(ReportCardComputationService::class)->compute($tenant->id, $classArm->id, $term->id);

        $positions = TermlySummary::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('class_arm_id', $classArm->id)
            ->where('term_id', $term->id)
            ->pluck('position_in_class')
            ->all();
        $this->assertSame([1, 1], array_map('intval', $positions));
    }

    private function score(
        int $tenantId,
        int $studentId,
        int $subjectId,
        int $termId,
        int $sessionId,
        int $classArmId,
        float $score,
    ): void {
        DB::table('scores')->insert([
            'tenant_id' => $tenantId,
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'assessment_type_id' => null,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'class_arm_id' => $classArmId,
            'score' => $score,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
