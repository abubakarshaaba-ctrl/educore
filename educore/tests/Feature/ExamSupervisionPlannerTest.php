<?php

namespace Tests\Feature;

use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\ClassLevel;
use App\Models\ExamPeriod;
use App\Models\ExamSession;
use App\Models\ExamSupervisor;
use App\Models\ExamTimetableEntry;
use App\Models\Scopes\TenantContext;
use App\Services\Exams\ExamSchedulerService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExamSupervisionPlannerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Exam supervision tests require the isolated sqlite :memory: test database.');
        }

        $this->rebuildSchema();
        TenantContext::set(1);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_supervision_plan_uses_active_staff_avoids_double_booking_and_reports_shortage(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'tenant_id' => 1, 'name' => 'Alice Teacher', 'is_active' => true],
            ['id' => 2, 'tenant_id' => 1, 'name' => 'Bob Teacher', 'is_active' => true],
            ['id' => 3, 'tenant_id' => 1, 'name' => 'Inactive Teacher', 'is_active' => false],
        ]);

        $period = ExamPeriod::create([
            'tenant_id' => 1,
            'term_id' => 1,
            'title' => 'Term Examination',
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-20',
            'status' => 'timetabled',
        ]);
        $level = ClassLevel::create(['tenant_id' => 1, 'name' => 'Basic 7']);
        $arm = ClassArm::create(['tenant_id' => 1, 'class_level_id' => $level->id, 'name' => 'Gold']);
        $session = ExamSession::create([
            'tenant_id' => 1,
            'exam_period_id' => $period->id,
            'name' => 'Morning',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'sort_order' => 1,
        ]);

        $period->classLevels()->attach($level->id, ['tenant_id' => 1]);
        $period->staffPool()->attach([
            1 => ['tenant_id' => 1],
            2 => ['tenant_id' => 1],
            3 => ['tenant_id' => 1],
        ]);

        ClassArmSubject::create([
            'tenant_id' => 1,
            'class_arm_id' => $arm->id,
            'subject_id' => 10,
            'teacher_id' => 1,
            'session_id' => 1,
        ]);
        ClassArmSubject::create([
            'tenant_id' => 1,
            'class_arm_id' => $arm->id,
            'subject_id' => 11,
            'teacher_id' => 2,
            'session_id' => 1,
        ]);

        $entries = collect([10, 11, 12])->map(fn (int $subjectId) => ExamTimetableEntry::create([
            'tenant_id' => 1,
            'exam_period_id' => $period->id,
            'class_level_id' => $level->id,
            'subject_id' => $subjectId,
            'exam_date' => '2026-07-20',
            'exam_session_id' => $session->id,
        ]));

        $result = app(ExamSchedulerService::class)->generateSupervision($period);

        $this->assertSame(['assigned' => 2, 'unassigned' => 1], $result);
        $this->assertSame(2, ExamSupervisor::count());
        $this->assertSame(2, ExamSupervisor::where('exam_timetable_entry_id', $entries[0]->id)->value('user_id'));
        $this->assertSame(1, ExamSupervisor::where('exam_timetable_entry_id', $entries[1]->id)->value('user_id'));
        $this->assertFalse(ExamSupervisor::where('user_id', 3)->exists());
        $this->assertSame('supervision_planned', $period->fresh()->status);
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'exam_supervisors',
            'exam_timetable_entries',
            'exam_period_staff',
            'exam_period_class_levels',
            'exam_sessions',
            'class_arm_subjects',
            'class_arms',
            'class_levels',
            'exam_periods',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('exam_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('term_id')->nullable();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('excluded_weekdays')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('class_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_arms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_arm_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->timestamps();
        });
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('exam_period_id');
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('exam_period_class_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('exam_period_id');
            $table->unsignedBigInteger('class_level_id');
        });
        Schema::create('exam_period_staff', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('exam_period_id');
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('exam_timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('exam_period_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('subject_id');
            $table->date('exam_date');
            $table->unsignedBigInteger('exam_session_id');
            $table->string('venue')->nullable();
            $table->timestamps();
        });
        Schema::create('exam_supervisors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('exam_timetable_entry_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }
}
