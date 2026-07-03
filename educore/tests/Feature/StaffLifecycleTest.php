<?php

namespace Tests\Feature;

use App\Console\Commands\RepairStaffWorkHistory;
use App\Http\Controllers\StaffController;
use App\Models\AuditLog;
use App\Models\PayrollItem;
use App\Models\Score;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffSalarySetting;
use App\Models\StaffStatusHistory;
use App\Models\StaffWorkHistory;
use App\Models\User;
use App\Http\Middleware\EnsureActiveAccount;
use App\Services\LifecycleAuditLogger;
use App\Services\StaffLifecycle\ChangeStaffStatus;
use App\Services\StaffLifecycle\RecordStaffWorkHistory;
use App\Services\StaffLifecycle\ReinstateStaff;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StaffLifecycleTest extends TestCase
{
    private const ALL_PERMISSIONS = [
        'staff.status.view',
        'staff.status.change',
        'staff.status.approve',
        'staff.archive.view',
        'staff.archive.export',
        'staff.reinstate',
        'staff.reinstate-terminated',
        'staff.work-history.view',
        'staff.work-history.manage',
        'staff.work-history.approve',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Staff lifecycle tests require the isolated sqlite :memory: test database.');
        }

        $this->rebuildSchema();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_active_staff_can_be_marked_left(): void
    {
        [$actor, $staff, $work] = $this->activeStaffFixture();

        $result = $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);

        $this->assertSame(User::STAFF_STATUS_LEFT, $result->employment_status);
        $this->assertFalse((bool) $result->is_active);
        $this->assertSame('2026-06-19', $result->employment_ended_at->toDateString());
        $this->assertSame('2026-06-19', $work->fresh()->end_date->toDateString());
        $this->assertSame(1, StaffStatusHistory::where('new_status', User::STAFF_STATUS_LEFT)->count());
        $this->assertSame(1, AuditLog::where('action', 'staff.left')->count());
    }

    public function test_active_staff_can_resign(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_RESIGNED);

        $this->assertSame(User::STAFF_STATUS_RESIGNED, $staff->fresh()->employment_status);
        $this->assertFalse((bool) $staff->fresh()->is_active);
        $this->assertSame(1, AuditLog::where('action', 'staff.resigned')->count());
    }

    public function test_authorised_administrator_can_terminate_staff(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_TERMINATED);

        $this->assertSame(User::STAFF_STATUS_TERMINATED, $staff->fresh()->employment_status);
        $this->assertSame(1, AuditLog::where('action', 'staff.terminated')->count());
    }

    public function test_unauthorised_user_cannot_change_staff_status(): void
    {
        [, $staff] = $this->activeStaffFixture();
        $actor = $this->makeUser('No Permission', 'accountant', []);

        $this->expectException(ValidationException::class);

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);
    }

    public function test_staff_cannot_deactivate_themselves(): void
    {
        [$actor] = $this->activeStaffFixture('admin');
        $actor->givePermissionTo(self::ALL_PERMISSIONS);

        $this->expectException(ValidationException::class);

        $this->changeStatus($actor, $actor, User::STAFF_STATUS_LEFT);
    }

    public function test_last_active_administrator_cannot_be_deactivated(): void
    {
        $actor = $this->makeUser('Lifecycle Officer', 'accountant', self::ALL_PERMISSIONS);
        $target = $this->makeUser('Only Admin', 'admin', [], 1, User::STAFF_STATUS_ACTIVE, true);

        $this->expectException(ValidationException::class);

        $this->changeStatus($actor, $target, User::STAFF_STATUS_LEFT);
    }

    public function test_exit_sets_status_and_is_active_false(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);

        $this->assertSame(User::STAFF_STATUS_LEFT, $staff->fresh()->employment_status);
        $this->assertFalse((bool) $staff->fresh()->is_active);
        $this->assertSame('Left school', $staff->fresh()->exit_reason);
    }

    public function test_exit_closes_open_work_history_row(): void
    {
        [$actor, $staff, $work] = $this->activeStaffFixture();

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_RESIGNED);

        $this->assertSame('2026-06-19', $work->fresh()->end_date->toDateString());
        $this->assertSame($actor->id, $work->fresh()->approved_by);
    }

    public function test_exit_writes_staff_status_history(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_TERMINATED);

        $history = StaffStatusHistory::firstOrFail();
        $this->assertSame(User::STAFF_STATUS_ACTIVE, $history->old_status);
        $this->assertSame(User::STAFF_STATUS_TERMINATED, $history->new_status);
        $this->assertSame($actor->id, $history->changed_by);
    }

    public function test_exit_writes_audit_log(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);

        $this->assertSame(1, AuditLog::where('action', 'staff.status.changed')->count());
    }

    public function test_archived_staff_cannot_log_in_because_is_active_is_false(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);

        $this->assertFalse((bool) $staff->fresh()->is_active);
    }

    public function test_archived_staff_excluded_from_attendance_entry_scope(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);

        $this->assertFalse(User::activeStaff(1)->whereKey($staff->id)->exists());
    }

    public function test_archived_staff_excluded_from_future_payroll_eligibility(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();
        StaffSalarySetting::create(['tenant_id' => 1, 'staff_id' => $staff->id, 'basic_salary' => 1000, 'is_active' => true]);

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);

        $settings = StaffSalarySetting::where('tenant_id', 1)
            ->whereHas('staff', fn ($query) => $query->payrollEligible(1))
            ->count();
        $this->assertSame(0, $settings);
    }

    public function test_archived_staff_excluded_from_new_timetable_and_subject_selectors(): void
    {
        [$actor, $staff] = $this->activeStaffFixture('subject_teacher');

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);

        $this->assertFalse(User::activeStaff(1)->teachers()->whereKey($staff->id)->exists());
    }

    public function test_historical_attendance_remains_after_exit(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();
        StaffAttendanceRecord::create(['tenant_id' => 1, 'user_id' => $staff->id, 'attendance_date' => '2026-06-01', 'status' => 'present']);

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);

        $this->assertSame(1, StaffAttendanceRecord::where('user_id', $staff->id)->count());
    }

    public function test_historical_payroll_remains_after_exit(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();
        PayrollItem::create(['tenant_id' => 1, 'staff_id' => $staff->id, 'net_pay' => 500]);

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_RESIGNED);

        $this->assertSame(1, PayrollItem::where('staff_id', $staff->id)->count());
    }

    public function test_historical_marks_entered_remain_attributable(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();
        Score::create(['tenant_id' => 1, 'entered_by' => $staff->id, 'score' => 70]);

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_TERMINATED);

        $this->assertSame($staff->id, Score::firstOrFail()->entered_by);
        $this->assertNotNull(User::find($staff->id));
    }

    public function test_left_staff_can_be_reinstated(): void
    {
        [$actor, $staff] = $this->archivedStaffFixture(User::STAFF_STATUS_LEFT);

        $result = $this->reinstate($actor, $staff);

        $this->assertSame(User::STAFF_STATUS_ACTIVE, $result->employment_status);
        $this->assertTrue((bool) $result->is_active);
    }

    public function test_resigned_staff_can_be_reinstated(): void
    {
        [$actor, $staff] = $this->archivedStaffFixture(User::STAFF_STATUS_RESIGNED);

        $this->reinstate($actor, $staff);

        $this->assertSame(User::STAFF_STATUS_ACTIVE, $staff->fresh()->employment_status);
    }

    public function test_terminated_reinstatement_requires_restricted_permission(): void
    {
        $actor = $this->makeUser('Principal', 'principal', ['staff.reinstate']);
        $staff = $this->makeUser('Terminated Staff', 'subject_teacher', [], 1, User::STAFF_STATUS_TERMINATED, false);

        $this->expectException(ValidationException::class);

        $this->reinstate($actor, $staff);
    }

    public function test_terminated_staff_can_be_reinstated_with_restricted_permission(): void
    {
        [$actor, $staff] = $this->archivedStaffFixture(User::STAFF_STATUS_TERMINATED);

        $this->reinstate($actor, $staff);

        $this->assertSame(User::STAFF_STATUS_ACTIVE, $staff->fresh()->employment_status);
    }

    public function test_reinstatement_creates_new_work_history_period(): void
    {
        [$actor, $staff] = $this->archivedStaffFixture(User::STAFF_STATUS_LEFT);

        $this->reinstate($actor, $staff);

        $this->assertSame(1, StaffWorkHistory::where('user_id', $staff->id)->whereNull('end_date')->count());
        $this->assertSame(StaffWorkHistory::CHANGE_REINSTATEMENT, StaffWorkHistory::where('user_id', $staff->id)->first()->change_type);
    }

    public function test_reinstatement_does_not_reopen_old_history(): void
    {
        [$actor, $staff] = $this->archivedStaffFixture(User::STAFF_STATUS_RESIGNED);
        $old = StaffWorkHistory::create([
            'tenant_id' => 1,
            'user_id' => $staff->id,
            'position_title' => 'Old Role',
            'start_date' => '2025-01-01',
            'end_date' => '2026-01-01',
            'change_type' => StaffWorkHistory::CHANGE_APPOINTMENT,
            'recorded_by' => $actor->id,
        ]);

        $this->reinstate($actor, $staff);

        $this->assertSame('2026-01-01', $old->fresh()->end_date->toDateString());
        $this->assertSame(2, StaffWorkHistory::where('user_id', $staff->id)->count());
    }

    public function test_duplicate_reinstatement_does_not_create_duplicate_open_histories(): void
    {
        [$actor, $staff] = $this->archivedStaffFixture(User::STAFF_STATUS_LEFT);

        $this->reinstate($actor, $staff);

        $this->expectException(ValidationException::class);
        $this->reinstate($actor, $staff->fresh());
    }

    public function test_archive_scope_includes_left_resigned_and_terminated(): void
    {
        $this->makeUser('Left', 'subject_teacher', [], 1, User::STAFF_STATUS_LEFT, false);
        $this->makeUser('Resigned', 'subject_teacher', [], 1, User::STAFF_STATUS_RESIGNED, false);
        $this->makeUser('Terminated', 'subject_teacher', [], 1, User::STAFF_STATUS_TERMINATED, false);

        $this->assertSame(3, User::archivedStaff(1)->count());
    }

    public function test_archive_scope_excludes_active_staff(): void
    {
        $this->makeUser('Active', 'subject_teacher', [], 1, User::STAFF_STATUS_ACTIVE, true);

        $this->assertSame(0, User::archivedStaff(1)->count());
    }

    public function test_cross_tenant_status_change_is_denied(): void
    {
        $actor = $this->makeUser('Admin A', 'admin', self::ALL_PERMISSIONS, 1);
        $staff = $this->makeUser('Staff B', 'subject_teacher', [], 2);

        $this->expectException(ModelNotFoundException::class);

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);
    }

    public function test_parent_student_and_platform_users_cannot_enter_staff_lifecycle(): void
    {
        $actor = $this->makeUser('Admin', 'admin', self::ALL_PERMISSIONS);
        $parent = $this->makeUser('Parent', 'parent', [], 1, User::STAFF_STATUS_ACTIVE, true);

        $this->expectException(ValidationException::class);

        $this->changeStatus($actor, $parent, User::STAFF_STATUS_LEFT);
    }

    public function test_work_history_promotion_closes_prior_row_and_creates_new_row(): void
    {
        [$actor, $staff, $current] = $this->activeStaffFixture();

        (new RecordStaffWorkHistory(new LifecycleAuditLogger()))->execute($actor, $staff, [
            'position_title' => 'Senior Teacher',
            'start_date' => '2026-06-19',
            'change_type' => StaffWorkHistory::CHANGE_PROMOTION,
            'reason' => 'Promotion',
        ]);

        $this->assertSame('2026-06-19', $current->fresh()->end_date->toDateString());
        $this->assertSame(1, StaffWorkHistory::where('user_id', $staff->id)->whereNull('end_date')->count());
        $this->assertSame(1, AuditLog::where('action', 'staff.work-history.created')->count());
    }

    public function test_repair_command_dry_run_makes_no_changes(): void
    {
        $staff = $this->makeUser('No History', 'subject_teacher', [], 1, User::STAFF_STATUS_ACTIVE, true);
        $staff->forceFill(['employment_started_at' => '2026-01-01'])->save();

        $this->artisan('lifecycle:repair-staff-work-history --dry-run')
            ->expectsOutputToContain('Dry run only')
            ->assertExitCode(0);

        $this->assertSame(0, StaffWorkHistory::where('user_id', $staff->id)->count());
    }

    public function test_new_staff_requires_employment_start_date(): void
    {
        $actor = $this->makeUser('Admin', 'admin', self::ALL_PERMISSIONS);

        $this->withoutMiddleware()
            ->actingAs($actor)
            ->post(route('staff.store'), [
                'name' => 'New Staff',
                'email' => 'new.staff@example.test',
                'role' => 'subject_teacher',
                'password' => 'Password123!',
                'position_title' => 'Teacher',
            ])
            ->assertSessionHasErrors('employment_started_at');
    }

    public function test_new_staff_creates_one_initial_open_work_history_row(): void
    {
        $actor = $this->makeUser('Admin', 'admin', self::ALL_PERMISSIONS);

        $this->withoutMiddleware()
            ->actingAs($actor)
            ->post(route('staff.store'), [
                'name' => 'New Staff',
                'email' => 'new.staff@example.test',
                'role' => 'subject_teacher',
                'password' => 'Password123!',
                'employment_started_at' => '2026-02-01',
                'position_title' => 'Teacher',
                'department_name' => 'Academics',
                'employment_type' => 'Full-time',
                'appointment_type' => 'Initial appointment',
            ])
            ->assertRedirect(route('staff.index'));

        $staff = User::where('email', 'new.staff@example.test')->firstOrFail();
        $this->assertSame(User::STAFF_STATUS_ACTIVE, $staff->employment_status);
        $this->assertTrue((bool) $staff->is_active);
        $this->assertSame('2026-02-01', $staff->employment_started_at->toDateString());
        $this->assertSame(1, StaffWorkHistory::where('user_id', $staff->id)->whereNull('end_date')->count());
        $this->assertSame(StaffWorkHistory::CHANGE_APPOINTMENT, StaffWorkHistory::where('user_id', $staff->id)->firstOrFail()->change_type);
    }

    public function test_profile_update_cannot_bypass_lifecycle_status_changes(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();

        $this->actingAs($actor);

        $request = Request::create(route('staff.update', $staff), 'PUT', [
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => $staff->role,
            'staff_id' => $staff->staff_id,
            'is_active' => 0,
            'employment_status' => User::STAFF_STATUS_TERMINATED,
        ]);
        $request->setLaravelSession(app('session.store'));

        app(StaffController::class)->update($request, $staff);

        $staff->refresh();
        $this->assertSame(User::STAFF_STATUS_ACTIVE, $staff->employment_status);
        $this->assertTrue((bool) $staff->is_active);
    }

    public function test_future_exit_date_is_rejected(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();

        $this->expectException(ValidationException::class);

        (new ChangeStaffStatus(new LifecycleAuditLogger()))->execute($actor, $staff, [
            'new_status' => User::STAFF_STATUS_LEFT,
            'effective_date' => now()->addDay()->toDateString(),
            'reason' => 'Future exit',
        ]);
    }

    public function test_exit_before_employment_start_is_rejected(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();

        $this->expectException(ValidationException::class);

        (new ChangeStaffStatus(new LifecycleAuditLogger()))->execute($actor, $staff, [
            'new_status' => User::STAFF_STATUS_LEFT,
            'effective_date' => '2025-12-31',
            'reason' => 'Invalid exit',
        ]);
    }

    public function test_future_reinstatement_is_rejected(): void
    {
        [$actor, $staff] = $this->archivedStaffFixture(User::STAFF_STATUS_LEFT);

        $this->expectException(ValidationException::class);

        (new ReinstateStaff(new LifecycleAuditLogger()))->execute($actor, $staff, [
            'effective_date' => now()->addDay()->toDateString(),
            'reason' => 'Future reinstatement',
            'position_title' => 'Teacher',
        ]);
    }

    public function test_overlapping_work_history_period_is_rejected(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();
        StaffWorkHistory::create([
            'tenant_id' => 1,
            'user_id' => $staff->id,
            'position_title' => 'Future Role',
            'start_date' => '2026-07-01',
            'end_date' => '2026-08-01',
            'change_type' => StaffWorkHistory::CHANGE_REASSIGNMENT,
            'recorded_by' => $actor->id,
        ]);

        $this->expectException(ValidationException::class);

        (new RecordStaffWorkHistory(new LifecycleAuditLogger()))->execute($actor, $staff, [
            'position_title' => 'Senior Teacher',
            'start_date' => '2026-06-19',
            'change_type' => StaffWorkHistory::CHANGE_PROMOTION,
            'reason' => 'Promotion',
        ]);
    }

    public function test_existing_session_is_denied_after_staff_exit(): void
    {
        Route::middleware(['web', EnsureActiveAccount::class])
            ->get('/_test-staff-active-account', fn () => 'ok')
            ->name('test.staff.active-account');

        [$actor, $staff] = $this->activeStaffFixture();
        $this->actingAs($staff);

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);

        $this->get('/_test-staff-active-account')
            ->assertRedirect(route('login'));
    }

    public function test_parent_student_and_super_admin_accounts_remain_unaffected_by_active_account_middleware(): void
    {
        Route::middleware(['web', EnsureActiveAccount::class])
            ->get('/_test-active-account-ok', fn () => 'ok')
            ->name('test.active-account.ok');

        $parent = $this->makeUser('Portal Parent', 'parent');
        $student = $this->makeUser('Portal Student', 'student');
        $superAdmin = $this->makeUser('Platform Admin', 'super_admin', [], null);
        $superAdmin->forceFill(['is_super_admin' => true])->save();

        $this->actingAs($parent)->get('/_test-active-account-ok')->assertOk();
        $this->actingAs($student)->get('/_test-active-account-ok')->assertOk();
        $this->actingAs($superAdmin)->get('/_test-active-account-ok')->assertOk();
    }

    public function test_payroll_period_eligibility_is_date_aware(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();
        StaffSalarySetting::create(['tenant_id' => 1, 'staff_id' => $staff->id, 'basic_salary' => 1000, 'is_active' => true]);

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);

        $historicalCount = StaffSalarySetting::where('tenant_id', 1)
            ->whereHas('staff', fn ($query) => $query->payrollEligibleForPeriod(1, '2026-05-01', '2026-05-31'))
            ->count();
        $laterCount = StaffSalarySetting::where('tenant_id', 1)
            ->whereHas('staff', fn ($query) => $query->payrollEligibleForPeriod(1, '2026-07-01', '2026-07-31'))
            ->count();

        $this->assertSame(1, $historicalCount);
        $this->assertSame(0, $laterCount);
    }

    public function test_reinstated_staff_is_payroll_eligible_only_from_new_period(): void
    {
        [$actor, $staff] = $this->archivedStaffFixture(User::STAFF_STATUS_LEFT);
        StaffSalarySetting::create(['tenant_id' => 1, 'staff_id' => $staff->id, 'basic_salary' => 1000, 'is_active' => true]);

        $this->reinstate($actor, $staff);

        $beforeReinstatement = StaffSalarySetting::where('tenant_id', 1)
            ->whereHas('staff', fn ($query) => $query->payrollEligibleForPeriod(1, '2026-02-01', '2026-02-28'))
            ->count();
        $afterReinstatement = StaffSalarySetting::where('tenant_id', 1)
            ->whereHas('staff', fn ($query) => $query->payrollEligibleForPeriod(1, '2026-06-20', '2026-06-30'))
            ->count();

        $this->assertSame(0, $beforeReinstatement);
        $this->assertSame(1, $afterReinstatement);
    }

    public function test_attendance_eligibility_is_date_aware(): void
    {
        [$actor, $staff] = $this->activeStaffFixture();

        $this->changeStatus($actor, $staff, User::STAFF_STATUS_LEFT);

        $this->assertTrue(User::attendanceEligibleOn(1, '2026-06-01')->whereKey($staff->id)->exists());
        $this->assertFalse(User::attendanceEligibleOn(1, '2026-07-01')->whereKey($staff->id)->exists());
    }

    public function test_last_admin_protection_is_tenant_specific(): void
    {
        $actor = $this->makeUser('Lifecycle Officer', 'accountant', self::ALL_PERMISSIONS, 1);
        $target = $this->makeUser('Tenant A Admin', 'admin', [], 1, User::STAFF_STATUS_ACTIVE, true);
        $this->makeUser('Tenant B Admin', 'admin', [], 2, User::STAFF_STATUS_ACTIVE, true);

        $this->expectException(ValidationException::class);

        $this->changeStatus($actor, $target, User::STAFF_STATUS_LEFT);
    }

    public function test_unknown_and_agent_roles_cannot_enter_staff_lifecycle(): void
    {
        $actor = $this->makeUser('Admin', 'admin', self::ALL_PERMISSIONS);
        $unknown = $this->makeUser('Unknown Role', 'custom_role', [], 1, User::STAFF_STATUS_ACTIVE, true);
        $agent = $this->makeUser('Agent User', 'agent', [], 1, User::STAFF_STATUS_ACTIVE, true);

        $this->assertFalse($unknown->isTenantStaff());
        $this->assertFalse($agent->isTenantStaff());

        try {
            $this->changeStatus($actor, $unknown, User::STAFF_STATUS_LEFT);
            $this->fail('Unknown role entered staff lifecycle.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $this->expectException(ValidationException::class);
        $this->changeStatus($actor, $agent, User::STAFF_STATUS_LEFT);
    }

    public function test_missing_employment_start_date_remains_unresolved_in_dry_run(): void
    {
        $staff = $this->makeUser('Missing Start', 'subject_teacher', [], 1, User::STAFF_STATUS_ACTIVE, true);
        $staff->forceFill(['employment_started_at' => null])->save();

        $this->artisan('lifecycle:repair-staff-work-history --dry-run')
            ->expectsOutputToContain('employment_started_at')
            ->assertExitCode(0);

        $this->assertSame(0, StaffWorkHistory::where('user_id', $staff->id)->count());
    }

    public function test_authorised_work_history_entry_can_complete_missing_employment_start_date(): void
    {
        $actor = $this->makeUser('Admin', 'admin', self::ALL_PERMISSIONS);
        $staff = $this->makeUser('Missing Start', 'subject_teacher', [], 1, User::STAFF_STATUS_ACTIVE, true);
        $staff->forceFill(['employment_started_at' => null])->save();

        (new RecordStaffWorkHistory(new LifecycleAuditLogger()))->execute($actor, $staff, [
            'position_title' => 'Teacher',
            'start_date' => '2026-03-01',
            'change_type' => StaffWorkHistory::CHANGE_APPOINTMENT,
            'reason' => 'Legacy employment start date correction',
        ]);

        $this->assertSame('2026-03-01', $staff->fresh()->employment_started_at->toDateString());
        $this->assertSame(1, StaffWorkHistory::where('user_id', $staff->id)->whereNull('end_date')->count());
    }

    private function activeStaffFixture(string $role = 'subject_teacher'): array
    {
        $actor = $this->makeUser('Admin', 'admin', self::ALL_PERMISSIONS);
        $staff = $this->makeUser('Staff', $role, [], 1, User::STAFF_STATUS_ACTIVE, true);
        $work = StaffWorkHistory::create([
            'tenant_id' => 1,
            'user_id' => $staff->id,
            'position_title' => 'Teacher',
            'start_date' => '2026-01-01',
            'change_type' => StaffWorkHistory::CHANGE_APPOINTMENT,
            'recorded_by' => $actor->id,
        ]);

        return [$actor, $staff, $work];
    }

    private function archivedStaffFixture(string $status): array
    {
        $actor = $this->makeUser('Admin', 'admin', self::ALL_PERMISSIONS);
        $staff = $this->makeUser('Archived Staff', 'subject_teacher', [], 1, $status, false);
        $staff->forceFill([
            'employment_started_at' => '2025-01-01',
            'employment_ended_at' => '2026-01-01',
            'exit_reason' => 'Previous exit',
        ])->save();

        return [$actor, $staff];
    }

    private function changeStatus(User $actor, User $staff, string $status): User
    {
        return (new ChangeStaffStatus(new LifecycleAuditLogger()))->execute($actor, $staff, [
            'new_status' => $status,
            'effective_date' => '2026-06-19',
            'reason' => 'Left school',
        ]);
    }

    private function reinstate(User $actor, User $staff): User
    {
        return (new ReinstateStaff(new LifecycleAuditLogger()))->execute($actor, $staff, [
            'effective_date' => '2026-06-19',
            'reason' => 'Reinstatement approved',
            'position_title' => 'Teacher',
        ]);
    }

    private function makeUser(
        string $name,
        string $role,
        array $permissions = [],
        ?int $tenantId = 1,
        string $employmentStatus = User::STAFF_STATUS_ACTIVE,
        bool $isActive = true
    ): User {
        if ($tenantId && (!Schema::hasTable('tenants') || !\DB::table('tenants')->where('id', $tenantId)->exists())) {
            \DB::table('tenants')->insert(['id' => $tenantId, 'name' => "Tenant {$tenantId}", 'created_at' => now(), 'updated_at' => now()]);
        }

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . ($tenantId ?? 'platform') . '@example.test',
            'password' => 'secret',
            'role' => $role,
            'staff_id' => 'STF' . random_int(1000, 9999),
            'is_active' => $isActive,
            'is_super_admin' => false,
            'employment_status' => $employmentStatus,
            'employment_started_at' => '2026-01-01',
        ]);

        foreach (array_unique($permissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        if ($permissions) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'model_has_permissions',
            'role_has_permissions',
            'model_has_roles',
            'permissions',
            'roles',
            'scores',
            'staff_salary_settings',
            'payroll_items',
            'staff_attendance_records',
            'audit_logs',
            'staff_work_histories',
            'staff_status_histories',
            'users',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->nullable();
            $table->string('staff_id')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super_admin')->default(false);
            $table->string('employment_status')->nullable();
            $table->date('employment_started_at')->nullable();
            $table->date('employment_ended_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->text('exit_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('staff_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->date('effective_date');
            $table->date('last_working_date')->nullable();
            $table->text('reason');
            $table->string('document_path')->nullable();
            $table->unsignedBigInteger('changed_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_work_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('position_title')->nullable();
            $table->string('department_name')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('functional_role')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('appointment_type')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('change_type');
            $table->text('reason')->nullable();
            $table->string('document_path')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->date('attendance_date');
            $table->string('status')->default('present');
            $table->timestamps();
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('staff_id');
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('staff_salary_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('staff_id');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('entered_by')->nullable();
            $table->integer('score')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }
}
