<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\MobileBootstrapController;
use App\Services\TenantAccessDecision;
use Tests\TestCase;

class MobileBootstrapSubscriptionContractTest extends TestCase
{
    public function test_mobile_bootstrap_exposes_authoritative_subscription_expiry_for_countdown(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/MobileBootstrapController.php'));

        $this->assertStringContainsString('billingTenant()', $source);
        $this->assertStringContainsString('subscription_expires_at', $source);
        $this->assertStringContainsString('TenantAccessDecision::STATE_FREE', $source);
        $this->assertStringContainsString("'expires_at' => $subscriptionExpiry?->toIso8601String()", $source);
        $this->assertStringContainsString("'server_time' => now()->toIso8601String()", $source);
    }

    public function test_bootstrap_controller_remains_invokable(): void
    {
        $this->assertTrue(method_exists(MobileBootstrapController::class, '__invoke'));
    }
}
