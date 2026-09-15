<?php

namespace Tests\Feature;

use App\Models\AlumniProfile;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentStatusHistory;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Services\AcademicCycleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\BuildsAcademicCycleTestSchema;
use Tests\TestCase;

class FinalClassGraduationTest extends TestCase
{
    use BuildsAcademicCycleTestSchema;

    private AcademicCycleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildAcademicCycleSchema();
        $this->createGraduationLifecycleTables();
        $this->service = app(AcademicCycleService::class);
    }

    public function test_closing_third_term_graduates_final_class_student_to_alumni(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2026/2027', true);

        $first = $this->createTerm($tenant->id, $session->id, 'First Term', '2026-09-01', '2026-12-15', false);
        $second = $this->createTerm($tenant->id, $session->id, 'Second Term', '2027-01-05', '2027-04-10', false);
        $third = $this->createTerm($tenant->id, $session->id, 'Third Term', '2027-04-20', '2027-07-25', true);

        $this->classArmFixture($tenant, 'Year 11', 11);
        $finalArm = $this->classArmFixture($tenant, 'Year 12', 12);
        $student = $this->studentFixture($tenant, $finalArm);
        $enrollment = $this->enrollmentFixture($tenant, $student, $finalArm, $session, $third);

        $this->service->closeTerm($tenant->id, $third->id, $actor);

        $student->refresh();
        $enrollment->refresh();

        $this->assertSame(Student::STATUS_GRADUATED, $student->status);
        $this->assertNull($student->current_class_arm_id);
        $this->assertSame('2027-07-25', $student->graduation_date?->toDateString());

        $this->assertFalse($enrollment->is_current);
        $this->assertSame(StudentEnrollment::STATUS_GRADUATED, $enrollment->status);
        $this->assertSame('2027-07-25', $enrollment->end_date?->toDateString());

        $alumni = AlumniProfile::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('student_id', $student->id)
            ->first();

        $this->assertNotNull($alumni);
        $this->assertSame('2027', $alumni->graduation_year);

        $this->assertSame(
            'graduated',
            TermlySummary::withoutTenantScope()
                ->where('student_id', $student->id)
                ->where('term_id', $third->id)
                ->value('promotion_status')
        );

        $this->assertDatabaseHas('student_status_histories', [
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'old_status' => Student::STATUS_ACTIVE,
            'new_status' => Student::STATUS_GRADUATED,
            'reason' => 'Completed final class level after third term',
        ]);
    }

    public function test_closing_second_term_does_not_graduate_final_class_student(): void
    {
        $tenant = $this->tenantFixture();
        $actor = $this->actorFixture($tenant);
        $session = $this->sessionFixture($tenant, '2026/2027', true);

        $this->createTerm($tenant->id, $session->id, 'First Term', '2026-09-01', '2026-12-15', false);
        $second = $this->createTerm($tenant->id, $session->id, 'Second Term', '2027-01-05', '2027-04-10', true);

        $finalArm = $this->classArmFixture($tenant, 'Year 12', 12);
        $student = $this->studentFixture($tenant, $finalArm);
        $enrollment = $this->enrollmentFixture($tenant, $student, $finalArm, $session, $second);

        $this->service->closeTerm($tenant->id, $second->id, $actor);

        $this->assertSame(Student::STATUS_ACTIVE, $student->fresh()->status);
        $this->assertTrue($enrollment->fresh()->is_current);
        $this->assertFalse(
            AlumniProfile::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('student_id', $student->id)
                ->exists()
        );
    }

    private function createTerm(
        int $tenantId,
        int $sessionId,
        string $name,
        string $startDate,
        string $endDate,
        bool $current
    ): Term {
        return Term::withoutTenantScope()->create([
            'tenant_id' => $tenantId,
            'session_id' => $sessionId,
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_current' => $current,
        ]);
    }

    private function createGraduationLifecycleTables(): void
    {
        Schema::create('alumni_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('graduation_year', 9)->nullable();
            $table->string('further_institution')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'student_id']);
        });

        Schema::create('student_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->date('effective_date');
            $table->text('reason')->nullable();
            $table->string('destination_school')->nullable();
            $table->string('transfer_certificate_number')->nullable();
            $table->string('document_path')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }
}
