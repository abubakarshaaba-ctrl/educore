<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministrativePasswordRecoveryContractTest extends TestCase
{
    #[Test]
    public function recovery_contract_is_wired_through_web_and_mobile_boundaries(): void
    {
        $root = base_path();

        $routes = file_get_contents($root . '/routes/web.php');
        $staff = file_get_contents($root . '/app/Http/Controllers/StaffController.php');
        $super = file_get_contents($root . '/app/Http/Controllers/SuperAdminController.php');
        $api = file_get_contents($root . '/app/Http/Middleware/AuthenticateApiToken.php');
        $bootstrap = file_get_contents($root . '/app/Http/Controllers/Api/MobileBootstrapController.php');

        $this->assertStringContainsString('account.password-required', $routes);
        $this->assertStringContainsString('tenant.admin.reset-password', $routes);
        $this->assertStringContainsString('AdministrativePasswordResetService', $staff);
        $this->assertStringContainsString('Only the tenant administrator can reset staff passwords', $staff);
        $this->assertStringContainsString('resetTenantAdminPassword', $super);
        $this->assertStringContainsString('password_change_required', $api);
        $this->assertStringContainsString('password_change_required', $bootstrap);
    }
}
