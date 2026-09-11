<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AdminStaffAttendanceOfflineSyncController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminStaffAttendanceOfflineSyncContractTest extends TestCase
{
    public function test_admin_offline_sync_route_uses_durable_sync_controller(): void
    {
        $route = collect(Route::getRoutes())->first(fn ($candidate) =>
            in_array('POST', $candidate->methods(), true)
            && $candidate->uri() === 'api/v1/admin/staff-attendance/offline/sync'
        );

        $this->assertNotNull($route);
        $this->assertStringContainsString(AdminStaffAttendanceOfflineSyncController::class, $route->getActionName());
    }

    public function test_admin_offline_sync_contract_uses_event_ledger_and_server_side_checks(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/AdminStaffAttendanceOfflineSyncController.php'));

        $this->assertStringContainsString('staff_attendance_sync_events', $source);
        $this->assertStringContainsString("['tenant_id' => \$tenantId, 'client_uuid' => \$data['client_uuid']]", $source);
        $this->assertStringContainsString("'idempotent' => true", $source);
        $this->assertStringContainsString("'status' => 'rejected'", $source);
        $this->assertStringContainsString('verifyStaticQrToken', $source);
        $this->assertStringContainsString('distanceTo', $source);
        $this->assertStringContainsString('A school attendance QR is required for clock-in.', $source);
        $this->assertStringContainsString('A clock-in record is required before clock-out.', $source);
        $this->assertStringContainsString('lockForUpdate', $source);
        $this->assertStringContainsString('catch (QueryException $exception)', $source);
    }

    public function test_admin_offline_sync_is_tenant_scoped_and_permission_guarded(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/AdminStaffAttendanceOfflineSyncController.php'));

        $this->assertStringContainsString("\$actor->canManage('staff-attendance')", $source);
        $this->assertStringContainsString('User::tenantStaff($actor->tenant_id)', $source);
        $this->assertStringContainsString("->where('tenant_id', \$actor->tenant_id)", $source);
        $this->assertStringContainsString("->where('tenant_id', \$tenantId)", $source);
    }
}
