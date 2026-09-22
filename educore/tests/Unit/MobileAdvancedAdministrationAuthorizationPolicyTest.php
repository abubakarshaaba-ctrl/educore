<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class MobileAdvancedAdministrationAuthorizationPolicyTest extends TestCase
{
    public function test_admin_portal_roles_are_centralized_and_include_school_leadership(): void
    {
        foreach (User::ADMIN_PORTAL_ROLES as $role) {
            $user = new User();
            $user->role = $role;

            $this->assertTrue(
                $user->isAdminPortalRole(),
                "Expected {$role} to be treated as an admin-portal role."
            );
        }

        foreach (['subject_teacher', 'accountant', 'admission_officer', 'student', 'parent'] as $role) {
            $user = new User();
            $user->role = $role;

            $this->assertFalse(
                $user->isAdminPortalRole(),
                "Did not expect {$role} to be treated as an admin-portal role."
            );
        }
    }

    public function test_mobile_bootstrap_and_advanced_admin_use_the_same_role_policy(): void
    {
        $root = dirname(__DIR__, 2);
        $bootstrap = file_get_contents($root . '/app/Http/Controllers/Api/MobileBootstrapController.php');
        $advanced = file_get_contents($root . '/app/Http/Controllers/Api/MobileAdvancedAdministrationController.php');

        $this->assertStringContainsString("isAdminPortalRole() ? 'admin' : 'staff'", $bootstrap);
        $this->assertStringContainsString('$actor->isAdminPortalRole()', $advanced);
        $this->assertStringNotContainsString('$actor->isAdmin() && $actor->tenant_id', $advanced);
    }
}
