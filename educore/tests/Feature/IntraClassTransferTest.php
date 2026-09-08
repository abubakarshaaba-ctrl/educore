<?php

namespace Tests\Feature;

use App\Models\ClassArm;
use App\Models\StudentClassTransfer;
use App\Models\StudentEnrollment;
use App\Services\StudentClassTransferService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\BuildsAcademicCycleTestSchema;
use Tests\TestCase;

class IntraClassTransferTest extends TestCase
{
    use BuildsAcademicCycleTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildAcademicCycleSchema();

        Schema::table('student_class_transfers', function (Blueprint $table): void {
            $table->string('movement_type', 30)
                ->default(StudentClassTransfer::TYPE_INTERCLASS)
                ->after('to_class_arm_id');
        });
    }

    public function test_intra_class_request_is_limited_to_another_arm_in_same_class_level(): void
    {
        $world = $this->world();
        $service = app(StudentClassTransferService::class);
        $this->actingAs($world['actor']);

        $transfer = $service->request(
            $world['actor'],
            $world['student']->id,
            $world['sibling']->id,
            '2026-02-01',
            'Balance enrolment between Primary 1 arms.',
            null,
            null,
            StudentClassTransfer::TYPE_INTRA_CLASS,
        );

        $this->assertSame(StudentClassTransfer::TYPE_INTRA_CLASS, $transfer->movement_type);
        $this->assertSame(StudentClassTransfer::STATUS_PENDING, $transfer->status);
        $this->assertSame($world['from']->class_level_id, $world['sibling']->class_level_id);
        $this->assertSame($world['from']->id, $world['student']->fresh()->current_class_arm_id);
        $this->assertTrue((bool) $world['enrollment']->fresh()->is_current);
    }

    public function test_intra_class_request_rejects_an_arm_in_a_different_class_level(): void
    {
        $world = $this->world();
        $service = app(StudentClassTransferService::class);
        $this->actingAs($world['actor']);

        try {
            $service->request(
                $world['actor'],
                $world['student']->id,
                $world['otherLevel']->id,
                '2026-02-01',
                'Invalid same-level request.',
                null,
                null,
                StudentClassTransfer::TYPE_INTRA_CLASS,
            );
            $this->fail('Expected the cross-level intra-class request to be rejected.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('to_class_arm_id', $error->errors());
        }

        $this->assertSame(0, StudentClassTransfer::withoutTenantScope()->count());
        $this->assertSame($world['from']->id, $world['student']->fresh()->current_class_arm_id);
    }

    public function test_interclass_request_rejects_an_arm_in_the_same_class_level(): void
    {
        $world = $this->world();
        $service = app(StudentClassTransferService::class);
        $this->actingAs($world['actor']);

        try {
            $service->request(
                $world['actor'],
                $world['student']->id,
                $world['sibling']->id,
                '2026-02-01',
                'Invalid cross-level request.',
                null,
                null,
                StudentClassTransfer::TYPE_INTERCLASS,
            );
            $this->fail('Expected the same-level interclass request to be rejected.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('to_class_arm_id', $error->errors());
        }

        $this->assertSame(0, StudentClassTransfer::withoutTenantScope()->count());
    }

    public function test_intra_class_approval_replaces_only_the_current_enrolment(): void
    {
        $world = $this->world();
        $service = app(StudentClassTransferService::class);
        $this->actingAs($world['actor']);

        $transfer = $service->request(
            $world['actor'],
            $world['student']->id,
            $world['sibling']->id,
            '2026-02-01',
            'Move learner from A to B.',
            null,
            null,
            StudentClassTransfer::TYPE_INTRA_CLASS,
        );
        $completed = $service->approve($world['actor'], $transfer->id);

        $this->assertSame(StudentClassTransfer::STATUS_COMPLETED, $completed->status);
        $this->assertSame(StudentClassTransfer::TYPE_INTRA_CLASS, $completed->movement_type);
        $this->assertSame($world['sibling']->id, $world['student']->fresh()->current_class_arm_id);

        $this->assertDatabaseHas('student_enrollments', [
            'id' => $world['enrollment']->id,
            'student_id' => $world['student']->id,
            'class_arm_id' => $world['from']->id,
            'is_current' => false,
            'status' => StudentEnrollment::STATUS_TRANSFERRED,
            'ended_reason' => 'Intra-class transfer #'.$transfer->id,
        ]);
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $world['student']->id,
            'class_arm_id' => $world['sibling']->id,
            'is_current' => true,
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);
        $this->assertSame(
            2,
            StudentEnrollment::withoutTenantScope()->where('student_id', $world['student']->id)->count(),
        );
    }

    public function test_approval_rechecks_same_level_boundary_after_request_creation(): void
    {
        $world = $this->world();
        $service = app(StudentClassTransferService::class);
        $this->actingAs($world['actor']);

        $transfer = $service->request(
            $world['actor'],
            $world['student']->id,
            $world['sibling']->id,
            '2026-02-01',
            'Same-level request.',
            null,
            null,
            StudentClassTransfer::TYPE_INTRA_CLASS,
        );

        $world['sibling']->forceFill([
            'class_level_id' => $world['otherLevel']->class_level_id,
        ])->save();

        try {
            $service->approve($world['actor'], $transfer->id);
            $this->fail('Expected the stale intra-class approval to be rejected.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('transfer', $error->errors());
        }

        $this->assertSame(StudentClassTransfer::STATUS_PENDING, $transfer->fresh()->status);
        $this->assertSame($world['from']->id, $world['student']->fresh()->current_class_arm_id);
        $this->assertTrue((bool) $world['enrollment']->fresh()->is_current);
        $this->assertSame(1, StudentEnrollment::withoutTenantScope()
            ->where('student_id', $world['student']->id)
            ->where('is_current', true)
            ->count());
    }

    private function world(): array
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2025/2026', true);
        $term = $this->termFixture($tenant, $session, 'Second Term', true);
        $from = $this->classArmFixture($tenant, 'Primary 1', 1, 'A');
        $sibling = ClassArm::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'class_level_id' => $from->class_level_id,
            'academic_track_id' => $from->academic_track_id,
            'name' => 'B',
        ]);
        $otherLevel = $this->classArmFixture($tenant, 'Primary 2', 2, 'A');
        $student = $this->studentFixture($tenant, $from);
        $enrollment = $this->enrollmentFixture($tenant, $student, $from, $session, $term, true);

        return compact(
            'tenant',
            'actor',
            'session',
            'term',
            'from',
            'sibling',
            'otherLevel',
            'student',
            'enrollment',
        );
    }
}
