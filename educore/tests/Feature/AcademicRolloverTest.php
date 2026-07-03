<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\AcademicCycleService;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\BuildsAcademicCycleTestSchema;
use Tests\TestCase;

class AcademicRolloverTest extends TestCase
{
    use BuildsAcademicCycleTestSchema;

    private AcademicCycleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildAcademicCycleSchema();
        $this->service = app(AcademicCycleService::class);
    }

    public function test_rollover_preview_performs_no_writes(): void
    {
        [$tenant, , $source, $target] = $this->rolloverFixture('promoted');

        $before = StudentEnrollment::withoutTenantScope()->count();
        $result = $this->service->previewRollover($tenant->id, $source->id, $target->id);

        $this->assertSame(1, $result->counts['ready']);
        $this->assertSame($before, StudentEnrollment::withoutTenantScope()->count());
    }

    public function test_commit_promotes_student_closes_source_and_preserves_history(): void
    {
        [$tenant, $actor, $source, $target, $student] = $this->rolloverFixture('promoted');
        $sourceEnrollment = $student->currentEnrollment()->first();
        Score::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'term_id' => $sourceEnrollment->term_id, 'session_id' => $source->id, 'score' => 88]);
        AttendanceRecord::withoutTenantScope()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'class_arm_id' => $sourceEnrollment->class_arm_id, 'term_id' => $sourceEnrollment->term_id, 'attendance_date' => '2026-03-01', 'status' => 'present']);

        $result = $this->service->commitRollover($tenant->id, $source->id, $target->id, $actor);

        $this->assertSame(1, $result->counts['promoted']);
        $this->assertFalse($sourceEnrollment->fresh()->is_current);
        $this->assertSame(StudentEnrollment::STATUS_CLOSED, $sourceEnrollment->fresh()->status);
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->where('is_current', true)->count());
        $this->assertSame($target->id, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->where('is_current', true)->first()->session_id);
        $this->assertSame(1, Score::withoutTenantScope()->count());
        $this->assertSame(1, AttendanceRecord::withoutTenantScope()->count());
    }

    public function test_repeat_creates_new_target_session_enrolment_without_reopening_old_row(): void
    {
        [$tenant, $actor, $source, $target, $student] = $this->rolloverFixture('repeat');

        $this->service->commitRollover($tenant->id, $source->id, $target->id, $actor);

        $this->assertSame(2, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->count());
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->where('is_current', true)->count());
        $this->assertSame($student->current_class_arm_id, $student->fresh()->current_class_arm_id);
    }

    public function test_terminal_graduation_updates_student_status_and_closes_enrolment(): void
    {
        [$tenant, $actor, $source, $target, $student] = $this->rolloverFixture('graduated', false);

        $this->service->commitRollover($tenant->id, $source->id, $target->id, $actor);

        $this->assertSame(Student::STATUS_GRADUATED, $student->fresh()->status);
        $this->assertNotNull($student->fresh()->graduation_date);
        $this->assertSame(0, StudentEnrollment::withoutTenantScope()->where('student_id', $student->id)->where('is_current', true)->count());
    }

    public function test_source_and_target_sessions_must_differ(): void
    {
        [$tenant, $actor, $source] = $this->rolloverFixture('promoted');

        $this->expectException(ValidationException::class);
        $this->service->commitRollover($tenant->id, $source->id, $source->id, $actor);
    }

    private function rolloverFixture(string $promotionStatus, bool $withNextClass = true): array
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $source = $this->sessionFixture($tenant, '2025/2026', true);
        $sourceTerm = $this->termFixture($tenant, $source, 'Third Term', true);
        $target = $this->sessionFixture($tenant, '2026/2027', false);
        $this->termFixture($tenant, $target, 'First Term', false);
        $sourceArm = $this->classArmFixture($tenant, 'Primary 1', 1, 'A');
        if ($withNextClass) {
            $this->classArmFixture($tenant, 'Primary 2', 2, 'A');
        }
        $student = $this->studentFixture($tenant, $sourceArm);
        $this->enrollmentFixture($tenant, $student, $sourceArm, $source, $sourceTerm);
        $this->summaryFixture($tenant, $student, $sourceArm, $sourceTerm, $promotionStatus);

        return [$tenant, $actor, $source, $target, $student];
    }
}
