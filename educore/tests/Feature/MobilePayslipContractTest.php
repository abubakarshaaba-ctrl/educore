<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\StaffCardController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MobilePayslipContractTest extends TestCase
{
    public function test_mobile_payslip_routes_are_registered_once(): void
    {
        $registered = collect(Route::getRoutes())->flatMap(function ($route) {
            return collect($route->methods())->map(fn (string $method): string => $method.' '.$route->uri());
        })->all();

        foreach ([
            'GET api/v1/payslips',
            'GET api/v1/payslips/{item}',
            'GET api/v1/payslips/{item}/pdf',
        ] as $signature) {
            $this->assertContains($signature, $registered, "Missing payslip route: {$signature}");
            $this->assertSame(1, count(array_keys($registered, $signature, true)), "Duplicate payslip route: {$signature}");
        }
    }

    public function test_payslip_controller_keeps_self_service_tenant_and_release_guards(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/StaffCardController.php'));

        $this->assertStringContainsString('isTenantStaff()', $source);
        $this->assertStringContainsString("where('staff_id', $user->id)", $source);
        $this->assertStringContainsString("where('tenant_id', $user->tenant_id)", $source);
        $this->assertStringContainsString("where('status', '!=', 'draft')", $source);
        $this->assertStringContainsString('(int) $item->staff_id === (int) $user->id', $source);
        $this->assertStringContainsString('(int) optional($item->period)->tenant_id === (int) $user->tenant_id', $source);
        $this->assertStringContainsString("optional($item->period)->status === 'draft'", $source);
    }

    public function test_mobile_pdf_uses_the_canonical_a4_portrait_template(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Api/StaffCardController.php'));
        $template = file_get_contents(resource_path('views/payroll/payslip-pdf.blade.php'));

        $this->assertStringContainsString("Pdf::loadView('payroll.payslip-pdf'", $controller);
        $this->assertStringContainsString("->setPaper('a4', 'portrait')", $controller);
        $this->assertStringContainsString('@page', $template);
        $this->assertStringContainsString('size: A4 portrait', $template);
    }
}
