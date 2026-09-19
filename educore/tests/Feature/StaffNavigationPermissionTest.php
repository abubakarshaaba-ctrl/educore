<?php

namespace Tests\Feature;

use App\Models\StaffPermission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Mobile\MobileModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffNavigationPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_hides_modules_not_available_to_subject_teacher_role(): void
    {
        [$tenant, $admin, $teacher] = $this->staffFixture('subject_teacher');

        $html = $this->actingAs($teacher)
            ->view('layouts.partials.full-nav')
            ->render();

        $this->assertStringContainsString('Lesson Planner', $html);
        $this->assertStringContainsString('Staff Attendance', $html);
        $this->assertStringContainsString('Scores', $html);
        $this->assertStringContainsString('CBT Exams', $html);
        $this->assertStringContainsString('Messages', $html);
        $this->assertStringContainsString('My Profile', $html);

        $this->assertStringNotContainsString('Visitor Log', $html);
        $this->assertStringNotContainsString('Staff Leave', $html);
        $this->assertStringNotContainsString('Platform Notices', $html);
        $this->assertStringNotContainsString('>Notices<', $html);
        $this->assertStringNotContainsString('Platform Support', $html);
        $this->assertStringNotContainsString('>Support<', $html);
        $this->assertStringNotContainsString('Academic Repository', $html);
        $this->assertStringNotContainsString('Skill Ratings', $html);
    }

    public function test_form_subject_teacher_sidebar_contains_only_role_granted_workspaces(): void
    {
        [, , $teacher] = $this->staffFixture('form_subject_teacher');

        $html = $this->actingAs($teacher)
            ->view('layouts.partials.full-nav')
            ->render();

        $this->assertStringContainsString('Lesson Planner', $html);
        $this->assertStringContainsString('Skill Ratings', $html);
        $this->assertStringContainsString('Staff Attendance', $html);
        $this->assertStringContainsString('data-tip="Scores"', $html);
        $this->assertStringContainsString('CBT Exams', $html);
        $this->assertStringContainsString('Messages', $html);
        $this->assertStringContainsString('My Profile', $html);

        $this->assertStringNotContainsString('Staff Leave', $html);
        $this->assertStringNotContainsString('Visitor Log', $html);
        $this->assertStringNotContainsString('Platform Notices', $html);
        $this->assertStringNotContainsString('Platform Support', $html);
        $this->assertStringNotContainsString('Academic Repository', $html);
    }

    public function test_explicit_grant_makes_module_visible_and_route_accessible(): void
    {
        [$tenant, $admin, $teacher] = $this->staffFixture('subject_teacher');

        StaffPermission::create([
            'tenant_id' => $tenant->id,
            'user_id' => $teacher->id,
            'module' => 'visitors',
            'type' => 'grant',
            'granted_by' => $admin->id,
        ]);

        $teacher = $teacher->fresh();

        $this->assertTrue($teacher->canAccessModule('visitors'));
        $this->assertTrue($teacher->canAccessRoute('visitors.index'));

        $html = $this->actingAs($teacher)
            ->view('layouts.partials.full-nav')
            ->render();

        $this->assertStringContainsString('Visitor Log', $html);
    }

    public function test_parent_deny_removes_child_permissions_and_hides_parent_workspace(): void
    {
        [$tenant, $admin, $teacher] = $this->staffFixture('subject_teacher');

        StaffPermission::create([
            'tenant_id' => $tenant->id,
            'user_id' => $teacher->id,
            'module' => 'scores',
            'type' => 'deny',
            'granted_by' => $admin->id,
        ]);

        $teacher = $teacher->fresh();

        $this->assertFalse($teacher->canAccessModule('scores'));
        $this->assertFalse($teacher->canAccessExactModule('scores.entry'));
        $this->assertFalse($teacher->canAccessRoute('scores.index'));
        $this->assertFalse(
            collect($teacher->effectivePermissionKeys())
                ->contains(fn (string $permission) =>
                    $permission === 'scores' || str_starts_with($permission, 'scores.')
                )
        );

        $nativeModules = collect(app(MobileModuleService::class)->forUser($teacher))
            ->pluck('key');
        $this->assertFalse($nativeModules->contains('scores'));

        $html = $this->actingAs($teacher)
            ->view('layouts.partials.full-nav')
            ->render();

        $this->assertStringNotContainsString('data-tip="Scores"', $html);
        $this->assertStringNotContainsString('data-tip="Parallel Curriculum"', $html);
    }

    public function test_denying_only_child_permission_hides_parent_when_it_is_the_only_child(): void
    {
        [$tenant, $admin, $teacher] = $this->staffFixture('subject_teacher');

        StaffPermission::create([
            'tenant_id' => $tenant->id,
            'user_id' => $teacher->id,
            'module' => 'scores.entry',
            'type' => 'deny',
            'granted_by' => $admin->id,
        ]);

        $teacher = $teacher->fresh();

        $this->assertFalse($teacher->canAccessExactModule('scores.entry'));
        $this->assertFalse($teacher->canAccessModule('scores'));
        $this->assertFalse($teacher->canAccessRoute('scores.index'));
    }

    public function test_leave_self_service_grant_does_not_grant_leave_approval(): void
    {
        [$tenant, $admin, $teacher] = $this->staffFixture('subject_teacher');

        StaffPermission::create([
            'tenant_id' => $tenant->id,
            'user_id' => $teacher->id,
            'module' => 'leave.self',
            'type' => 'grant',
            'granted_by' => $admin->id,
        ]);

        $teacher = $teacher->fresh();

        $this->assertTrue($teacher->canAccessModule('leave'));
        $this->assertTrue($teacher->canAccessRoute('leave.index'));
        $this->assertTrue($teacher->canAccessRoute('leave.store'));
        $this->assertTrue($teacher->canAccessRoute('leave.cancel'));
        $this->assertFalse($teacher->canAccessRoute('leave.approve'));
        $this->assertFalse($teacher->canAccessRoute('leave.reject'));
    }

    public function test_admission_officer_legacy_academic_access_remains_hidden_until_explicitly_granted(): void
    {
        [$tenant, $admin, $officer] = $this->staffFixture('admission_officer');

        $this->assertFalse($officer->canAccessModule('students'));
        $this->assertFalse($officer->canAccessRoute('students.index'));

        StaffPermission::create([
            'tenant_id' => $tenant->id,
            'user_id' => $officer->id,
            'module' => 'students',
            'type' => 'grant',
            'granted_by' => $admin->id,
        ]);

        $officer = $officer->fresh();

        $this->assertTrue($officer->canAccessModule('students'));
        $this->assertTrue($officer->canAccessRoute('students.index'));
    }

    private function staffFixture(string $role): array
    {
        $tenant = Tenant::create([
            'name' => 'Permission Navigation School',
            'slug' => 'permission-navigation-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Permission Admin',
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Permission Staff',
            'role' => $role,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        return [$tenant, $admin, $staff];
    }
}
