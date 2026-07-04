<?php

namespace Tests\Feature;

use App\Http\Controllers\StaffAttendanceController;
use App\Models\CbtExam;
use App\Models\CbtQuestionBank;
use App\Models\ClassArm;
use App\Models\Invoice;
use App\Models\OnlinePaymentLog;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentTransaction;
use App\Models\StaffAttendanceRecord;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditRemediationSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Audit remediation tests require the isolated sqlite :memory: test database.');
        }

        $this->rebuildSchema();
    }

    public function test_paystack_webhook_requires_valid_signature_and_matching_amount_before_crediting_invoice(): void
    {
        $tenant = $this->tenantFixture();
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'student_id' => null,
            'invoice_number' => 'INV-001',
            'total_amount' => 5000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        PaymentGatewayConfig::create([
            'tenant_id' => $tenant->id,
            'gateway' => 'paystack',
            'public_key' => 'pk_test_public',
            'secret_key' => 'sk_test_secret',
            'is_live' => false,
            'is_active' => true,
        ]);

        $log = OnlinePaymentLog::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'student_id' => null,
            'gateway' => 'paystack',
            'reference' => 'SMS-SECURE123',
            'amount' => 5000,
            'status' => 'pending',
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => $log->reference,
                'status' => 'success',
                'amount' => 500000,
                'currency' => 'NGN',
            ],
        ];

        $this->postRawPaystackWebhook($payload)->assertStatus(401);
        $this->assertSame('pending', $log->fresh()->status);
        $this->assertSame(0.0, (float) $invoice->fresh()->amount_paid);

        $payload['data']['amount'] = 100;
        $this->postRawPaystackWebhook($payload, 'sk_test_secret')->assertOk();
        $this->assertSame('failed', $log->fresh()->status);
        $this->assertSame(0.0, (float) $invoice->fresh()->amount_paid);

        $payload['data']['amount'] = 500000;
        $this->postRawPaystackWebhook($payload, 'sk_test_secret')->assertOk();
        $this->assertSame('success', $log->fresh()->status);
        $this->assertSame(5000.0, (float) $invoice->fresh()->amount_paid);
        $this->assertSame(1, PaymentTransaction::count());
    }

    public function test_tenant_admin_cannot_use_super_admin_subscription_extension_route(): void
    {
        $tenant = $this->tenantFixture();
        $targetTenant = $this->tenantFixture(['slug' => 'target-school', 'name' => 'Target School']);
        $admin = $this->userFixture($tenant, ['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('super.tenant.extend', $targetTenant), ['months' => 1])
            ->assertForbidden();
    }

    public function test_staff_id_card_qr_cannot_proxy_clock_in_another_staff_member(): void
    {
        $tenant = $this->tenantFixture();
        $scanner = $this->userFixture($tenant, ['email' => 'scanner@example.test', 'staff_id' => 'STF001']);
        $target = $this->userFixture($tenant, ['email' => 'target@example.test', 'staff_id' => 'STF002']);

        $this->actingAs($scanner);
        $request = Request::create('/staff-attendance/clock-in', 'POST', [
            'token' => $target->personalQrPayload(),
        ]);

        $response = app(StaffAttendanceController::class)->clockInQr($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($response->getData()->ok);
        $this->assertSame(0, StaffAttendanceRecord::count());
    }

    public function test_students_cannot_start_published_cbt_exam_before_scheduled_window(): void
    {
        $tenant = $this->tenantFixture();
        $classArm = ClassArm::create([
            'tenant_id' => $tenant->id,
            'class_level_id' => 1,
            'name' => 'A',
        ]);
        $studentUser = $this->userFixture($tenant, ['role' => 'student', 'email' => 'student@example.test']);
        Student::create([
            'tenant_id' => $tenant->id,
            'user_id' => $studentUser->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'current_class_arm_id' => $classArm->id,
            'status' => Student::STATUS_ACTIVE,
        ]);

        $bank = CbtQuestionBank::create([
            'tenant_id' => $tenant->id,
            'subject_id' => 1,
            'class_level_id' => 1,
            'name' => 'Mathematics',
        ]);
        $exam = CbtExam::create([
            'tenant_id' => $tenant->id,
            'question_bank_id' => $bank->id,
            'class_arm_id' => $classArm->id,
            'title' => 'Future Exam',
            'duration_minutes' => 30,
            'total_questions' => 1,
            'total_marks' => 1,
            'scheduled_start' => now()->addHour(),
            'scheduled_end' => now()->addHours(2),
            'status' => 'published',
        ]);

        $this->actingAs($studentUser)
            ->get(route('cbt.exams.start', $exam))
            ->assertRedirect(route('student.portal.exams'))
            ->assertSessionHas('info', 'This exam has not started yet.');
    }

    private function postRawPaystackWebhook(array $payload, ?string $secret = null)
    {
        $raw = json_encode($payload);
        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($secret) {
            $headers['HTTP_X_PAYSTACK_SIGNATURE'] = hash_hmac('sha512', $raw, $secret);
        }

        return $this->call('POST', route('webhooks.paystack'), [], [], [], $headers, $raw);
    }

    private function tenantFixture(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Bluerayy Academy',
            'slug' => 'bluerayy-academy',
            'email' => 'info@bluerayy.test',
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
        ], $overrides));
    }

    private function userFixture(Tenant $tenant, array $overrides = []): User
    {
        return User::create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Test User',
            'email' => 'user'.random_int(1000, 9999).'@example.test',
            'password' => Hash::make('password'),
            'role' => 'subject_teacher',
            'is_super_admin' => false,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ], $overrides));
    }

    private function rebuildSchema(): void
    {
        foreach ([
            'cbt_exams',
            'cbt_question_banks',
            'students',
            'class_arms',
            'staff_attendance_records',
            'staff_attendance_settings',
            'payment_transactions',
            'online_payment_logs',
            'payment_gateway_configs',
            'invoices',
            'users',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('status')->default(Tenant::STATUS_ACTIVE);
            $table->date('subscription_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('staff_id')->nullable()->unique();
            $table->string('student_id')->nullable()->unique();
            $table->string('password');
            $table->boolean('is_super_admin')->default(false);
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('employment_status', 40)->nullable();
            $table->string('attendance_pin')->nullable();
            $table->string('qr_secret')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('invoice_number');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('gateway')->default('paystack');
            $table->text('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->text('contract_code')->nullable();
            $table->boolean('is_live')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('online_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('gateway');
            $table->string('reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            $table->json('gateway_response')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('gateway_reference')->unique();
            $table->string('gateway');
            $table->decimal('amount_paid', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status');
            $table->json('gateway_response')->nullable();
            $table->json('split_breakdown')->nullable();
            $table->string('paid_by_name')->nullable();
            $table->string('paid_by_phone')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->time('resumption_time')->default('08:00:00');
            $table->integer('grace_minutes')->default(15);
            $table->time('closing_time')->default('15:00:00');
            $table->decimal('geo_lat', 10, 7)->nullable();
            $table->decimal('geo_lng', 10, 7)->nullable();
            $table->integer('geo_radius_meters')->default(100);
            $table->boolean('geo_enabled')->default(false);
            $table->string('qr_secret')->nullable();
            $table->date('qr_secret_date')->nullable();
            $table->string('permanent_qr_secret')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->date('attendance_date');
            $table->string('status')->nullable();
            $table->time('clock_in_time')->nullable();
            $table->timestamps();
        });

        Schema::create('class_arms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('status')->default(Student::STATUS_ACTIVE);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cbt_question_banks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('cbt_exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('question_bank_id');
            $table->unsignedBigInteger('term_id')->nullable();
            $table->unsignedBigInteger('class_arm_id');
            $table->string('title');
            $table->integer('duration_minutes')->default(30);
            $table->integer('total_questions')->default(0);
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->integer('section_objective_count')->default(0);
            $table->decimal('section_objective_marks', 8, 2)->default(1);
            $table->integer('section_theory_count')->default(0);
            $table->decimal('section_theory_marks', 8, 2)->default(5);
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }
}
