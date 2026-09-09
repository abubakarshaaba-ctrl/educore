<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformInvoicePdfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform invoice PDF tests require sqlite :memory:.');
        }

        foreach (['platform_settings', 'platform_invoices', 'api_tokens', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('email')->nullable();
            $table->string('address')->nullable(); $table->string('status')->default(Tenant::STATUS_ACTIVE); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->string('name'); $table->string('email')->nullable()->unique();
            $table->string('role')->nullable(); $table->boolean('is_super_admin')->default(false); $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('name'); $table->string('token', 64)->unique();
            $table->string('device')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        Schema::create('platform_invoices', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->string('invoice_number')->unique(); $table->decimal('amount', 12, 2);
            $table->unsignedInteger('student_count')->default(0); $table->string('billing_cycle'); $table->string('status')->default('pending');
            $table->date('due_date'); $table->text('notes')->nullable(); $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable(); $table->string('payment_ref')->nullable(); $table->timestamps();
        });
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->id(); $table->string('key')->unique(); $table->text('value')->nullable(); $table->timestamps();
        });
    }

    public function test_super_admin_can_download_official_invoice_pdf(): void
    {
        $tenant = Tenant::create([
            'name' => 'PDF School', 'slug' => 'pdf-school', 'email' => 'school@example.test',
            'address' => 'Abuja, Nigeria', 'status' => Tenant::STATUS_ACTIVE,
        ]);
        $invoiceId = DB::table('platform_invoices')->insertGetId([
            'tenant_id' => $tenant->id, 'invoice_number' => 'INV-PDF001', 'amount' => 25000,
            'student_count' => 75, 'billing_cycle' => 'termly', 'status' => 'pending',
            'due_date' => now()->addDays(14)->toDateString(), 'notes' => 'Term subscription',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('platform_settings')->insert([
            ['key' => 'platform_name', 'value' => 'EduCore', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'support_email', 'value' => 'support@example.test', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $super = $this->user('PDF Super Admin', true, null);

        $response = $this->withToken(ApiToken::issue($super, 'invoice-pdf'))
            ->get('/api/v1/platform/billing/invoices/'.$invoiceId.'/pdf');

        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('EduCore-Invoice-INV-PDF001.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_non_super_admin_cannot_download_platform_invoice_pdf(): void
    {
        $tenant = Tenant::create(['name' => 'Protected PDF School', 'slug' => 'protected-pdf-school', 'status' => Tenant::STATUS_ACTIVE]);
        $invoiceId = DB::table('platform_invoices')->insertGetId([
            'tenant_id' => $tenant->id, 'invoice_number' => 'INV-PDF002', 'amount' => 10000,
            'student_count' => 60, 'billing_cycle' => 'termly', 'status' => 'pending',
            'due_date' => now()->addDays(7)->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $admin = $this->user('Tenant Admin', false, $tenant->id);

        $this->withToken(ApiToken::issue($admin, 'invoice-pdf-denied'))
            ->get('/api/v1/platform/billing/invoices/'.$invoiceId.'/pdf')
            ->assertForbidden();
    }

    public function test_missing_invoice_pdf_returns_not_found(): void
    {
        $super = $this->user('Missing PDF Super Admin', true, null);
        $this->withToken(ApiToken::issue($super, 'invoice-pdf-missing'))
            ->get('/api/v1/platform/billing/invoices/999999/pdf')
            ->assertNotFound();
    }

    private function user(string $name, bool $super, ?int $tenantId): User
    {
        return User::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => str($name)->slug().'.'.uniqid().'@example.test',
            'role' => $super ? 'super_admin' : 'admin',
            'is_super_admin' => $super,
            'is_active' => true,
            'employment_status' => $super ? null : User::STAFF_STATUS_ACTIVE,
        ]);
    }
}
