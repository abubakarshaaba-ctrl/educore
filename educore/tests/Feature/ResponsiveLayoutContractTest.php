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

    public function test_admissions_surfaces_keep_compact_metrics_and_responsive_actions(): void
    {
        $admissions = file_get_contents(resource_path('views/admissions/index.blade.php'));
        $detail = file_get_contents(resource_path('views/admissions/show.blade.php'));
        $portal = file_get_contents(resource_path('views/admissions/portal-list.blade.php'));

        $this->assertStringContainsString('.stats-row{grid-template-columns:repeat(2,minmax(0,1fr))', $admissions);
        $this->assertStringContainsString('.ph-actions{width:100%;display:grid;grid-template-columns:1fr 1fr', $admissions);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $admissions);
        $this->assertStringContainsString('.tbl table{min-width:820px}', $admissions);

        $this->assertStringContainsString('@media(max-width:900px){.page-grid{grid-template-columns:1fr}', $detail);
        $this->assertStringContainsString('@media(max-width:380px){.info-row{flex-direction:column', $detail);

        $this->assertStringContainsString('.sg{grid-template-columns:repeat(2,minmax(0,1fr))', $portal);
        $this->assertStringContainsString('.portal-actions{width:100%;display:grid;grid-template-columns:1fr 1fr', $portal);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $portal);
    }

    public function test_people_management_and_timetable_keep_mobile_safe_actions_and_scroll_boundaries(): void
    {
        $students = file_get_contents(resource_path('views/students/index.blade.php'));
        $staff = file_get_contents(resource_path('views/staff/index.blade.php'));
        $timetable = file_get_contents(resource_path('views/timetable/index.blade.php'));

        $this->assertStringContainsString('.student-page-actions { width:100%; display:grid; grid-template-columns:repeat(2,minmax(0,1fr));', $students);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $students);
        $this->assertStringContainsString('.student-table-card table { min-width:700px; }', $students);

        $this->assertStringContainsString('.page-actions{display:grid;grid-template-columns:1fr;width:100%}', $staff);
        $this->assertStringContainsString('.tbl{overflow-x:auto;-webkit-overflow-scrolling:touch}', $staff);
        $this->assertStringContainsString('table{min-width:720px}', $staff);

        $this->assertStringContainsString('.two-col { grid-template-columns:1fr; }', $timetable);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $timetable);
        $this->assertStringContainsString('.page-tabs::-webkit-scrollbar{display:none}', $timetable);
    }

    public function test_score_entry_surfaces_keep_mobile_forms_and_grade_sheets_usable(): void
    {
        $index = file_get_contents(resource_path('views/scores/index.blade.php'));
        $entry = file_get_contents(resource_path('views/scores/entry.blade.php'));
        $broadsheet = file_get_contents(resource_path('views/scores/broadsheet.blade.php'));

        $this->assertStringContainsString('.form-group.full{grid-column:auto}', $index);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $index);
        $this->assertStringNotContainsString('style="grid-column:span 2"', $index);

        $this->assertStringContainsString('.sheet-wrap { width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch', $entry);
        $this->assertStringContainsString('position:sticky;left:0', $entry);
        $this->assertStringContainsString('.sheet-footer>div:last-child{display:grid!important;grid-template-columns:1fr 1fr', $entry);

        $this->assertStringContainsString('position:sticky;left:0', $broadsheet);
        $this->assertStringContainsString('.sheet-outer{overflow-x:auto', $broadsheet);
    }

    public function test_grading_and_timetable_frequency_keep_phone_safe_forms_and_tables(): void
    {
        $grading = file_get_contents(resource_path('views/settings/grading.blade.php'));
        $frequency = file_get_contents(resource_path('views/timetable/frequency.blade.php'));

        $this->assertStringContainsString('.grade-table-wrap{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch', $grading);
        $this->assertStringContainsString('.add-grade-grid{grid-template-columns:1fr}', $grading);
        $this->assertStringContainsString('.snav{position:relative;top:0;display:flex;gap:4px;overflow-x:auto;-webkit-overflow-scrolling:touch', $grading);

        $this->assertStringContainsString('.freq-table-wrap{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch', $frequency);
        $this->assertStringContainsString('.selector-grid { grid-template-columns:1fr; }', $frequency);
        $this->assertStringContainsString('.total-bar{align-items:stretch;flex-direction:column', $frequency);
    }

    public function test_assessment_template_surfaces_keep_tabs_tables_modals_and_actions_mobile_safe(): void
    {
        $templates = file_get_contents(resource_path('views/scores/assessment-types.blade.php'));
        $legacy = file_get_contents(resource_path('views/scores/assessment-types-admin.blade.php'));

        $this->assertStringContainsString('.at-tabs{display:flex', $templates);
        $this->assertStringContainsString('-webkit-overflow-scrolling:touch', $templates);
        $this->assertStringContainsString('.at-table-wrap{width:100%;max-width:100%;overflow-x:auto', $templates);
        $this->assertStringContainsString('.at-modal-backdrop{padding:8px;align-items:flex-end}', $templates);
        $this->assertStringContainsString('.at-checks{grid-template-columns:1fr 1fr}', $templates);

        $this->assertStringContainsString('.assessment-scroll { width:100%; max-width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }', $legacy);
        $this->assertStringContainsString('.assessment-actions { display:grid; grid-template-columns:1fr 1fr; width:100%; }', $legacy);
        $this->assertStringContainsString('.assessment-layout { grid-template-columns:1fr; }', $legacy);
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
