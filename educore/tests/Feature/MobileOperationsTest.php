<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\Invoice;
use App\Models\LibraryBook;
use App\Models\SchoolAsset;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use App\Services\Mobile\MobileModuleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileOperationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile operations tests require sqlite :memory:.');
        }

        foreach (['assets', 'library_books', 'invoices', 'students', 'terms', 'academic_sessions', 'api_tokens', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('role')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->string('device')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('academic_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
        Schema::create('terms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id');
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('next_term_begins')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('admission_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('term_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('invoice_number');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('library_books', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('isbn')->nullable();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('category')->nullable();
            $table->string('location')->nullable();
            $table->unsignedInteger('total_copies')->default(0);
            $table->unsignedInteger('available_copies')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('condition')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_receives_tenant_scoped_read_first_operations_contracts(): void
    {
        [$tenant, $admin, $term] = $this->school('Greenfield');
        [$otherTenant, , $otherTerm] = $this->school('Other School');
        $student = Student::create(['tenant_id' => $tenant->id, 'admission_number' => 'STU001', 'first_name' => 'Amina', 'last_name' => 'Bello']);
        $otherStudent = Student::create(['tenant_id' => $otherTenant->id, 'admission_number' => 'OTHER1', 'first_name' => 'Other', 'last_name' => 'Student']);
        Invoice::create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'term_id' => $term->id, 'session_id' => $term->session_id, 'invoice_number' => 'INV-001', 'total_amount' => 100000, 'amount_paid' => 40000, 'status' => 'partially_paid']);
        Invoice::create(['tenant_id' => $otherTenant->id, 'student_id' => $otherStudent->id, 'term_id' => $otherTerm->id, 'session_id' => $otherTerm->session_id, 'invoice_number' => 'OTHER-INV', 'total_amount' => 999, 'status' => 'unpaid']);
        LibraryBook::create(['tenant_id' => $tenant->id, 'title' => 'Things Fall Apart', 'author' => 'Chinua Achebe', 'total_copies' => 4, 'available_copies' => 3, 'is_active' => true]);
        SchoolAsset::create(['tenant_id' => $tenant->id, 'name' => 'Science Laboratory Microscope', 'category' => 'Laboratory', 'condition' => 'good', 'status' => 'available']);
        SchoolAsset::create(['tenant_id' => $otherTenant->id, 'name' => 'Other Asset', 'category' => 'Office']);
        $token = ApiToken::issue($admin, 'operations-test');

        $this->withToken($token)->getJson('/api/v1/operations/fees')
            ->assertOk()->assertJsonPath('contract_version', 1)
            ->assertJsonPath('module.key', 'fees')->assertJsonPath('module.mobile_policy', 'read_first')
            ->assertJsonCount(1, 'sections.0.records')->assertJsonPath('sections.0.records.0.title', 'INV-001')
            ->assertJsonPath('metrics.2.value', '₦60,000.00');

        $this->withToken($token)->getJson('/api/v1/operations/library')
            ->assertOk()->assertJsonPath('sections.0.records.0.title', 'Things Fall Apart');
        $this->withToken($token)->getJson('/api/v1/operations/inventory')
            ->assertOk()->assertJsonCount(1, 'sections.0.records')
            ->assertJsonPath('sections.0.records.0.title', 'Science Laboratory Microscope');
        $this->withToken($token)->getJson('/api/v1/operations/academic-cycle')
            ->assertOk()->assertJsonPath('sections.0.records.0.status', 'current')
            ->assertJsonPath('sections.1.records.0.title', 'First Term');
    }

    public function test_operations_reject_missing_token_forbidden_role_and_unknown_module(): void
    {
        [$tenant, $admin] = $this->school('Access School');
        $teacher = User::create(['tenant_id' => $tenant->id, 'name' => 'Subject Teacher', 'role' => 'subject_teacher', 'is_active' => true, 'employment_status' => User::STAFF_STATUS_ACTIVE]);

        $this->getJson('/api/v1/operations/library')->assertUnauthorized();
        $this->withToken(ApiToken::issue($teacher, 'teacher-operations'))->getJson('/api/v1/operations/library')->assertForbidden();
        $this->withToken(ApiToken::issue($admin, 'unknown-operations'))->getJson('/api/v1/operations/not-real')->assertNotFound();
    }

    public function test_mobile_module_discovery_exposes_authorized_operations_without_duplicates(): void
    {
        [, $admin] = $this->school('Module School');
        $keys = collect(app(MobileModuleService::class)->forUser($admin))->pluck('key');

        $this->assertTrue($keys->contains('academic-cycle'));
        $this->assertTrue($keys->contains('inventory'));
        $this->assertTrue($keys->contains('hostels'));
        $this->assertSame($keys->count(), $keys->unique()->count());
    }

    private function school(string $name): array
    {
        $tenant = Tenant::create(['name' => $name, 'slug' => str($name)->slug().'-'.uniqid(), 'status' => 'active']);
        $admin = User::create(['tenant_id' => $tenant->id, 'name' => $name.' Admin', 'role' => 'admin', 'is_active' => true, 'employment_status' => User::STAFF_STATUS_ACTIVE]);
        $session = AcademicSession::create(['tenant_id' => $tenant->id, 'name' => '2026/2027', 'is_current' => true]);
        $term = Term::create(['tenant_id' => $tenant->id, 'session_id' => $session->id, 'name' => 'First Term', 'start_date' => '2026-09-01', 'end_date' => '2026-12-15', 'is_current' => true]);

        return [$tenant, $admin, $term];
    }
}
