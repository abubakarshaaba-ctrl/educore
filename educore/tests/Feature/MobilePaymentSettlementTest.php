<?php

namespace Tests\Feature;

use App\Models\OnlinePaymentLog;
use App\Services\SchoolFeePaymentService;
use App\Services\SubscriptionPaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MobilePaymentSettlementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildPaymentSchema();
    }

    public function test_verified_subscription_payment_extends_expiry_and_capacity_once(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Payment School',
            'slug' => 'payment-school',
            'status' => 'active',
            'subscription_expires_at' => now()->addDays(10)->toDateString(),
            'students_capacity' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invoiceId = DB::table('platform_invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'invoice_number' => 'INV-TEST-1',
            'amount' => 25000,
            'student_count' => 120,
            'billing_cycle' => 'termly',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(SubscriptionPaymentService::class);
        $first = $service->settle($invoiceId, 'SUB-TEST-1', 'paystack_online');
        $firstExpiry = $first->subscription_expires_at?->toDateString();

        $this->assertSame('paid', DB::table('platform_invoices')->where('id', $invoiceId)->value('status'));
        $this->assertSame(120, (int) DB::table('tenants')->where('id', $tenantId)->value('students_capacity'));
        $this->assertSame(1, DB::table('platform_payments')->where('reference', 'SUB-TEST-1')->count());

        $second = $service->settle($invoiceId, 'SUB-TEST-1', 'paystack_online');

        $this->assertSame(1, DB::table('platform_payments')->where('reference', 'SUB-TEST-1')->count());
        $this->assertSame($firstExpiry, $second->subscription_expires_at?->toDateString());
    }

    public function test_verified_school_fee_payment_updates_invoice_and_creates_one_ledger_record(): void
    {
        $tenantId = $this->tenantFixture();
        $invoiceId = DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'student_id' => 501,
            'invoice_number' => 'FEE-TEST-1',
            'total_amount' => 100000,
            'amount_paid' => 25000,
            'status' => 'partially_paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $logId = DB::table('online_payment_logs')->insertGetId([
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'student_id' => 501,
            'gateway' => 'paystack',
            'reference' => 'FEE-REF-1',
            'amount' => 75000,
            'status' => 'success',
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(SchoolFeePaymentService::class);
        $log = OnlinePaymentLog::withoutGlobalScopes()->findOrFail($logId);
        $service->settle($log);
        $service->settle($log->fresh());

        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        $this->assertSame(100000.0, (float) $invoice->amount_paid);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(1, DB::table('payment_transactions')->where('gateway_reference', 'FEE-REF-1')->count());
    }

    public function test_fee_settlement_uses_server_invoice_balance_and_never_overcredits(): void
    {
        $tenantId = $this->tenantFixture('clamp-school');
        $invoiceId = DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'student_id' => 502,
            'invoice_number' => 'FEE-TEST-2',
            'total_amount' => 100000,
            'amount_paid' => 90000,
            'status' => 'partially_paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $logId = DB::table('online_payment_logs')->insertGetId([
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'student_id' => 502,
            'gateway' => 'flutterwave',
            'reference' => 'FEE-REF-2',
            'amount' => 50000,
            'status' => 'success',
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(SchoolFeePaymentService::class)->settle(OnlinePaymentLog::withoutGlobalScopes()->findOrFail($logId));

        $this->assertSame(100000.0, (float) DB::table('invoices')->where('id', $invoiceId)->value('amount_paid'));
        $this->assertSame(10000.0, (float) DB::table('payment_transactions')->where('gateway_reference', 'FEE-REF-2')->value('amount_paid'));
    }

    public function test_unverified_school_fee_log_cannot_be_credited(): void
    {
        $tenantId = $this->tenantFixture('unverified-school');
        $invoiceId = DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'student_id' => 503,
            'invoice_number' => 'FEE-TEST-3',
            'total_amount' => 50000,
            'amount_paid' => 0,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $logId = DB::table('online_payment_logs')->insertGetId([
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'student_id' => 503,
            'gateway' => 'monnify',
            'reference' => 'FEE-REF-3',
            'amount' => 50000,
            'status' => 'pending',
            'verified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            app(SchoolFeePaymentService::class)->settle(OnlinePaymentLog::withoutGlobalScopes()->findOrFail($logId));
            $this->fail('An unverified fee payment must not be credited.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        $this->assertSame(0.0, (float) DB::table('invoices')->where('id', $invoiceId)->value('amount_paid'));
        $this->assertSame(0, DB::table('payment_transactions')->count());
    }

    public function test_payment_routes_expose_scoped_mobile_contract_and_not_client_paid_flags(): void
    {
        $routes = file_get_contents(base_path('routes/api.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Api/MobilePaymentController.php'));

        $this->assertStringContainsString("Route::get('subscription'", $routes);
        $this->assertStringContainsString("subscription/invoices/{invoice}/bank-transfer", $routes);
        $this->assertStringContainsString("Route::get('fees/children/{student}'", $routes);
        $this->assertStringContainsString("Route::get('fees/payments'", $routes);
        $this->assertStringContainsString('Only the school administrator can manage subscription payments', $controller);
        $this->assertStringContainsString('This child is not linked to your parent account', $controller);
        $this->assertStringNotContainsString('paid=true', $controller);
        $this->assertStringContainsString('verifyPlatformPayment', $controller);
        $this->assertStringContainsString('verifyTenantPayment', $controller);
    }

    public function test_mobile_module_contract_keeps_subscription_admin_only_and_cbt_absent(): void
    {
        $modules = file_get_contents(app_path('Services/Mobile/MobileModuleService.php'));
        $apiRoutes = file_get_contents(base_path('routes/api.php'));

        $this->assertStringContainsString("'subscription' => ['Subscription & Billing'", $modules);
        $this->assertStringContainsString("if (\$key === 'subscription')", $modules);
        $this->assertStringContainsString("return \$roleKey === 'admin';", $modules);
        $this->assertStringNotContainsString("['key' => 'cbt'", $modules);
        $this->assertStringNotContainsString("Route::prefix('cbt')->group", $apiRoutes, 'CBT must not be exposed as a normal mobile module route.');
    }

    private function tenantFixture(string $slug = 'fee-school'): int
    {
        return DB::table('tenants')->insertGetId([
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'status' => 'active',
            'students_capacity' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function rebuildPaymentSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['payment_transactions', 'online_payment_logs', 'invoices', 'students', 'platform_payments', 'platform_invoices', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->nullable();
            $table->date('subscription_expires_at')->nullable();
            $table->unsignedInteger('students_capacity')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('invoice_number');
            $table->decimal('amount', 14, 2);
            $table->unsignedInteger('student_count')->nullable();
            $table->string('billing_cycle');
            $table->string('status');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_ref')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('platform_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('reference')->unique();
            $table->decimal('amount', 14, 2);
            $table->string('currency')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('invoice_number');
            $table->unsignedBigInteger('term_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->decimal('total_amount', 14, 2);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->string('status')->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
        Schema::create('online_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('student_id');
            $table->string('gateway');
            $table->string('reference')->unique();
            $table->decimal('amount', 14, 2);
            $table->string('status');
            $table->json('gateway_response')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('student_id');
            $table->string('gateway_reference')->unique();
            $table->string('gateway');
            $table->decimal('amount_paid', 14, 2);
            $table->string('currency')->nullable();
            $table->string('status')->nullable();
            $table->json('gateway_response')->nullable();
            $table->json('split_breakdown')->nullable();
            $table->string('paid_by_name')->nullable();
            $table->string('paid_by_phone')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
}
