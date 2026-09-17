<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResponsiveLayoutContractTest extends TestCase
{
    #[DataProvider('shells')]
    public function test_primary_shells_keep_mobile_viewport_and_responsive_breakpoints(string $path): void
    {
        $contents = file_get_contents(base_path($path));

        $this->assertIsString($contents);
        $this->assertStringContainsString('width=device-width, initial-scale=1.0', $contents);
        $this->assertMatchesRegularExpression('/@media\s*\([^)]*max-width\s*:\s*(?:768|820|900)px/i', $contents);
    }

    public function test_super_admin_control_centre_keeps_compact_mobile_metrics(): void
    {
        $health = file_get_contents(resource_path('views/super/system-health.blade.php'));
        $audit = file_get_contents(resource_path('views/super/audit-security.blade.php'));
        $tenant = file_get_contents(resource_path('views/super/tenant-operations.blade.php'));

        $this->assertStringContainsString('.health-summary{grid-template-columns:repeat(3,minmax(0,1fr))!important', $health);
        $this->assertStringContainsString('.asc-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important', $audit);
        $this->assertStringContainsString('.ops-stats{grid-template-columns:repeat(2,minmax(0,1fr))', $tenant);
    }

    public function test_super_admin_data_tables_remain_touch_scrollable(): void
    {
        $audit = file_get_contents(resource_path('views/super/audit-security.blade.php'));
        $tenant = file_get_contents(resource_path('views/super/tenant-operations.blade.php'));

        $this->assertStringContainsString('overflow-x:auto', $audit);
        $this->assertStringContainsString('overflow-x:auto', $tenant);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $audit);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $tenant);
    }

    public function test_primary_application_brand_css_keeps_two_column_mobile_kpis_and_scrollable_tables(): void
    {
        $css = file_get_contents(public_path('brand/educore-brand.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('grid-template-columns:repeat(2,minmax(0,1fr)) !important', $css);
        $this->assertStringContainsString('overflow-x:auto !important', $css);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $css);
    }

    public function test_finance_and_payroll_screens_keep_compact_mobile_cards_and_touch_tables(): void
    {
        $payrollIndex = file_get_contents(resource_path('views/payroll/index.blade.php'));
        $payslips = file_get_contents(resource_path('views/payroll/payslip.blade.php'));
        $invoices = file_get_contents(resource_path('views/fees/invoices.blade.php'));

        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $payrollIndex);
        $this->assertStringContainsString('.payroll-actions{display:grid;grid-template-columns:1fr 1fr', $payrollIndex);

        $this->assertStringContainsString('.payroll-stats{grid-template-columns:repeat(2,minmax(0,1fr))!important', $payslips);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $payslips);

        $this->assertStringContainsString('.invoice-summary{grid-template-columns:repeat(2,minmax(0,1fr))!important', $invoices);
        $this->assertStringContainsString('.invoice-filters{grid-template-columns:1fr', $invoices);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $invoices);
    }

    public function test_report_management_remains_usable_on_phone_widths(): void
    {
        $publications = file_get_contents(resource_path('views/reports/publications.blade.php'));
        $remarks = file_get_contents(resource_path('views/reports/remarks.blade.php'));

        $this->assertStringContainsString('.stats-row{grid-template-columns:repeat(2,minmax(0,1fr))', $publications);
        $this->assertStringContainsString('overflow-x:auto;-webkit-overflow-scrolling:touch', $publications);
        $this->assertStringContainsString('.pub-actions{display:grid;grid-template-columns:1fr 1fr', $publications);

        $this->assertStringContainsString('.remarks-table .tbl{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch', $remarks);
        $this->assertStringContainsString('.filter-card{grid-template-columns:1fr', $remarks);
        $this->assertStringContainsString('min-width:760px', $remarks);
    }

    public function test_admissions_dashboard_keeps_compact_metrics_and_touch_table(): void
    {
        $admissions = file_get_contents(resource_path('views/admissions/index.blade.php'));

        $this->assertStringContainsString('.stats-row{grid-template-columns:repeat(2,minmax(0,1fr))', $admissions);
        $this->assertStringContainsString('.ph-actions{width:100%;display:grid;grid-template-columns:1fr 1fr', $admissions);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $admissions);
        $this->assertStringContainsString('.tbl table{min-width:820px}', $admissions);
    }

    public static function shells(): array
    {
        return [
            'application shell' => ['resources/views/layouts/app.blade.php'],
            'super admin shell' => ['resources/views/layouts/super.blade.php'],
            'portal shell' => ['resources/views/layouts/portal.blade.php'],
            'authentication shell' => ['resources/views/layouts/auth.blade.php'],
            'question builder shell' => ['resources/views/layouts/builder.blade.php'],
        ];
    }
}
