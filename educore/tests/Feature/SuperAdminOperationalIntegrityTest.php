<?php

namespace Tests\Feature;

use App\Http\Controllers\TenantOperationsController;
use App\Models\StaffWorkHistory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SuperAdminOperationalIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Operational integrity tests require the isolated sqlite :memory: test database.');
        }

        foreach (['platform_invoices', 'platform_support_tickets', 'staff_work_histories', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        $this->createTenantTable();
        $this->createSupportTable();
        $this->createInvoiceTable();
        $this->createStaffWorkHistoryTable();
    }

    public function test_attention_filter_is_applied_before_pagination_and_keeps_independent_reasons(): void
    {
        for ($i = 1; $i <= 35; $i++) {
            DB::table('tenants')->insert([
                'name' => sprintf('Healthy School %02d', $i),
                'slug' => sprintf('healthy-school-%02d', $i),
                'status' => 'active',
                'subscription_expires_at' => now()->addMonths(6)->toDateString(),
            ]);
        }

        $suspendedId = DB::table('tenants')->insertGetId([
            'name' => 'ZZ Suspended School',
            'slug' => 'zz-suspended-school',
            'status' => 'suspended',
            'subscription_expires_at' => now()->addMonths(6)->toDateString(),
        ]);
        $supportId = DB::table('tenants')->insertGetId([
            'name' => 'ZZ Support School',
            'slug' => 'zz-support-school',
            'status' => 'active',
            'subscription_expires_at' => now()->addMonths(6)->toDateString(),
        ]);
        $expiringId = DB::table('tenants')->insertGetId([
            'name' => 'ZZ Expiring School',
            'slug' => 'zz-expiring-school',
            'status' => 'active',
            'subscription_expires_at' => now()->addDays(7)->toDateString(),
        ]);
        $invoiceId = DB::table('tenants')->insertGetId([
            'name' => 'ZZ Invoice School',
            'slug' => 'zz-invoice-school',
            'status' => 'active',
            'subscription_expires_at' => now()->addMonths(6)->toDateString(),
        ]);

        DB::table('platform_support_tickets')->insert([
            'tenant_id' => $supportId,
            'status' => 'open',
        ]);
        DB::table('platform_invoices')->insert([
            'tenant_id' => $invoiceId,
            'status' => 'unpaid',
        ]);

        Paginator::currentPageResolver(fn () => 1);
        $request = Request::create('/super/tenant-operations', 'GET', ['attention' => '1']);
        $view = app(TenantOperationsController::class)->index($request);
        $data = $view->getData();

        $this->assertSame(4, $data['tenants']->total());
        $this->assertSame(
            collect([$suspendedId, $supportId, $expiringId, $invoiceId])->sort()->values()->all(),
            $data['rows']->pluck('tenant.id')->sort()->values()->all()
        );
        $this->assertTrue($data['rows']->every(fn ($row) => count($row->attention) > 0));
    }

    public function test_initial_admin_provisioning_does_not_fabricate_full_time_employment(): void
    {
        StaffWorkHistory::create([
            'tenant_id' => 10,
            'user_id' => 20,
            'position_title' => 'School Administrator',
            'functional_role' => 'admin',
            'employment_type' => 'full_time',
            'appointment_type' => 'initial_admin',
            'start_date' => now()->toDateString(),
            'change_type' => StaffWorkHistory::CHANGE_APPOINTMENT,
            'reason' => 'Initial tenant administrator provisioned.',
            'recorded_by' => 1,
        ]);

        $history = StaffWorkHistory::withoutTenantScope()->firstOrFail();

        $this->assertNull($history->employment_type);
        $this->assertSame('initial_admin', $history->appointment_type);
    }

    private function createTenantTable(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('status')->nullable();
            $table->date('subscription_expires_at')->nullable();
            $table->softDeletes();
        });
    }

    private function createSupportTable(): void
    {
        Schema::create('platform_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('status')->nullable();
        });
    }

    private function createInvoiceTable(): void
    {
        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('status')->nullable();
        });
    }

    private function createStaffWorkHistoryTable(): void
    {
        Schema::create('staff_work_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('position_title')->nullable();
            $table->string('department_name')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('functional_role')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('appointment_type')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('change_type');
            $table->text('reason')->nullable();
            $table->string('document_path')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }
}
