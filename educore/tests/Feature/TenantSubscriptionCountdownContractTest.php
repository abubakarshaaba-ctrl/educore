<?php

namespace Tests\Feature;

use Tests\TestCase;

class TenantSubscriptionCountdownContractTest extends TestCase
{
    public function test_mobile_bootstrap_exposes_authoritative_subscription_expiry(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/MobileBootstrapController.php'));

        $this->assertStringContainsString('$billingTenant = $tenant?->billingTenant();', $source);
        $this->assertStringContainsString('$subscriptionExpiry = $access->expiresAt;', $source);
        $this->assertStringContainsString('$subscriptionExpiry ??= $billingTenant?->subscription_expires_at;', $source);
        $this->assertStringContainsString("'expires_at' => $subscriptionExpiry?->toIso8601String()", $source);
        $this->assertStringContainsString("'server_time' => now()->toIso8601String()", $source);
    }

    public function test_android_dashboard_renders_admin_subscription_countdown_from_server_time(): void
    {
        $path = dirname(base_path()).'/mobile-native/app/src/main/java/online/educoreng/educore/presentation/DashboardHomeContent.kt';
        $this->assertFileExists($path);

        $source = file_get_contents($path);

        $this->assertStringContainsString('if (session.user.portal == "admin")', $source);
        $this->assertStringContainsString('SubscriptionCountdownTile(session)', $source);
        $this->assertStringContainsString('session.serverTime?.let(::parseLocalDate)', $source);
        $this->assertStringContainsString('session.access.expiresAt?.let(::parseLocalDate)', $source);
        $this->assertStringContainsString('ChronoUnit.DAYS.between(today, it)', $source);
        $this->assertStringContainsString('remaining != null && remaining <= 7', $source);
        $this->assertStringContainsString('remaining != null && remaining <= 30', $source);
        $this->assertStringContainsString('"Expires today"', $source);
        $this->assertStringContainsString('"1 day remaining"', $source);
        $this->assertStringContainsString('"$remaining days remaining"', $source);
    }
}
