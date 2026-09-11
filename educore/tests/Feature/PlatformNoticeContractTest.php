<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\PlatformNoticeController;
use App\Http\Controllers\SuperAdminPlatformBroadcastController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PlatformNoticeContractTest extends TestCase
{
    public function test_mobile_platform_notice_routes_are_registered_once(): void
    {
        $registered = collect(Route::getRoutes())->flatMap(function ($route) {
            return collect($route->methods())->map(fn (string $method): string => $method.' '.$route->uri());
        })->all();

        foreach ([
            'GET api/v1/platform-notices',
            'POST api/v1/platform-notices/{broadcast}/read',
            'POST api/v1/platform-notices/{broadcast}/dismiss',
        ] as $signature) {
            $this->assertContains($signature, $registered);
            $this->assertSame(1, count(array_keys($registered, $signature, true)));
        }
    }

    public function test_platform_notice_feed_is_tenant_and_user_specific_with_role_aware_audiences(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/PlatformNoticeController.php'));

        $this->assertStringContainsString('$user->tenant_id', $source);
        $this->assertStringContainsString('$user->is_active', $source);
        $this->assertStringContainsString('$user->isTenantStaff()', $source);
        $this->assertStringContainsString('$user->isEmploymentActive()', $source);
        $this->assertStringContainsString("'platform_broadcast_user_reads'", $source);
        $this->assertStringContainsString("'broadcast_id' => $broadcast", $source);
        $this->assertStringContainsString("'user_id' => $user->id", $source);
        $this->assertStringContainsString("'tenant_id' => $user->tenant_id", $source);
        $this->assertStringContainsString("->whereIn('target', ['all', $tenantStatus])", $source);
        $this->assertStringContainsString("->where('audience', 'all_accounts')", $source);
        $this->assertStringContainsString("orWhere('audience', 'staff')", $source);
        $this->assertStringContainsString("orWhere('audience', 'admins')", $source);
    }

    public function test_super_admin_platform_publisher_validates_scope_audience_and_priority(): void
    {
        $registered = collect(Route::getRoutes())->first(fn ($route) =>
            in_array('POST', $route->methods(), true)
            && $route->uri() === 'super/platform-broadcasts'
        );

        $this->assertNotNull($registered);
        $this->assertStringContainsString(SuperAdminPlatformBroadcastController::class, $registered->getActionName());

        $source = file_get_contents(app_path('Http/Controllers/SuperAdminPlatformBroadcastController.php'));
        $this->assertStringContainsString("'target' => ['required', 'in:all,trial,active,expired']", $source);
        $this->assertStringContainsString("'audience' => ['required', 'in:staff,admins,all_accounts']", $source);
        $this->assertStringContainsString("'priority' => ['required', 'in:normal,high,urgent']", $source);
        $this->assertStringContainsString('$user->isSuperAdmin()', $source);
    }

    public function test_platform_notice_controller_actions_exist(): void
    {
        foreach (['index', 'markRead', 'dismiss'] as $method) {
            $this->assertTrue(method_exists(PlatformNoticeController::class, $method));
        }
        $this->assertTrue(method_exists(SuperAdminPlatformBroadcastController::class, 'store'));
    }
}
