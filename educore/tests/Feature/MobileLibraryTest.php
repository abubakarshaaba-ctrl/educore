<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileLibraryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile library tests require sqlite :memory:.');
        }

        foreach (['library_loans', 'library_books', 'students', 'staff_permissions', 'api_tokens', 'users', 'tenants'] as $table) {
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
            $table->string('staff_id')->nullable();
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
        Schema::create('staff_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('module', 60);
            $table->string('type')->default('grant');
            $table->unsignedBigInteger('granted_by');
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id', 'module']);
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('admission_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('library_books', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('isbn')->nullable();
            $table->string('title');
            $table->string('author');
            $table->string('publisher')->nullable();
            $table->string('edition')->nullable();
            $table->integer('year')->nullable();
            $table->string('category');
            $table->string('location')->nullable();
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->string('condition')->default('good');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('library_loans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->string('status')->default('issued');
            $table->decimal('fine_amount', 8, 2)->default(0);
            $table->boolean('fine_paid')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamps();
        });
    }

    public function test_options_are_tenant_scoped_for_librarian(): void
    {
        [$tenant, $librarian] = $this->school('Library School', 'librarian');
        [$foreignTenant] = $this->school('Foreign Library School', 'librarian');
        $book = $this->book($tenant->id, 'Native Biology', 2);
        $this->book($foreignTenant->id, 'Foreign Biology', 4);
        $student = $this->student($tenant->id, 'LIB-001', 'Amina', 'Bello');
        $this->student($foreignTenant->id, 'FOREIGN-001', 'Foreign', 'Student');

        $response = $this->withToken(ApiToken::issue($librarian, 'library-options'))
            ->getJson('/api/v1/library/options')
            ->assertOk()
            ->assertJsonCount(1, 'books')
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('books.0.id', $book->id)
            ->assertJsonPath('students.0.id', $student->id);

        $this->assertFalse(collect($response->json('books'))->contains(fn ($item) => $item['title'] === 'Foreign Biology'));
    }

    public function test_issue_decrements_available_copy_and_creates_tenant_scoped_loan(): void
    {
        [$tenant, $librarian] = $this->school('Issue Library School', 'librarian');
        $book = $this->book($tenant->id, 'Issue Me', 2);
        $student = $this->student($tenant->id, 'LIB-002', 'Musa', 'Aliyu');

        $this->withToken(ApiToken::issue($librarian, 'library-issue'))
            ->postJson('/api/v1/library/loans', [
                'book_id' => $book->id,
                'student_id' => $student->id,
                'due_date' => now()->addDays(7)->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('loan.status', 'issued')
            ->assertJsonPath('loan.student_id', $student->id);

        $this->assertSame(1, (int) $book->fresh()->available_copies);
        $this->assertDatabaseHas('library_loans', [
            'tenant_id' => $tenant->id,
            'book_id' => $book->id,
            'student_id' => $student->id,
            'status' => 'issued',
        ]);
    }

    public function test_issue_requires_exactly_one_borrower(): void
    {
        [$tenant, $librarian] = $this->school('Borrower Library School', 'librarian');
        $book = $this->book($tenant->id, 'Borrower Rule', 1);
        $student = $this->student($tenant->id, 'LIB-003', 'Zainab', 'Sani');

        $this->withToken(ApiToken::issue($librarian, 'library-borrower-rule'))
            ->postJson('/api/v1/library/loans', [
                'book_id' => $book->id,
                'student_id' => $student->id,
                'staff_id' => $librarian->id,
                'due_date' => now()->addDays(7)->toDateString(),
            ])
            ->assertUnprocessable();

        $this->assertSame(1, (int) $book->fresh()->available_copies);
        $this->assertSame(0, LibraryLoan::count());
    }

    public function test_issue_rejects_foreign_book_and_borrower(): void
    {
        [, $librarian] = $this->school('Boundary Library School', 'librarian');
        [$foreignTenant] = $this->school('Foreign Boundary Library', 'librarian');
        $foreignBook = $this->book($foreignTenant->id, 'Foreign Book', 1);
        $foreignStudent = $this->student($foreignTenant->id, 'LIB-X', 'Foreign', 'Borrower');

        $this->withToken(ApiToken::issue($librarian, 'library-boundary'))
            ->postJson('/api/v1/library/loans', [
                'book_id' => $foreignBook->id,
                'student_id' => $foreignStudent->id,
                'due_date' => now()->addDays(7)->toDateString(),
            ])
            ->assertUnprocessable();
    }

    public function test_return_restores_copy_and_cannot_cross_tenant_boundary(): void
    {
        [$tenant, $librarian] = $this->school('Return Library School', 'librarian');
        $book = $this->book($tenant->id, 'Return Me', 2, available: 1);
        $student = $this->student($tenant->id, 'LIB-004', 'Halima', 'Garba');
        $loan = LibraryLoan::create([
            'tenant_id' => $tenant->id,
            'book_id' => $book->id,
            'student_id' => $student->id,
            'issue_date' => now()->subDays(2)->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'issued',
            'issued_by' => $librarian->id,
        ]);

        $this->withToken(ApiToken::issue($librarian, 'library-return'))
            ->postJson('/api/v1/library/loans/'.$loan->id.'/return')
            ->assertOk()
            ->assertJsonPath('loan.status', 'returned');

        $this->assertSame(2, (int) $book->fresh()->available_copies);

        [$foreignTenant, $foreignLibrarian] = $this->school('Other Return School', 'librarian');
        $foreignBook = $this->book($foreignTenant->id, 'Other Book', 1, available: 0);
        $foreignStudent = $this->student($foreignTenant->id, 'LIB-005', 'Other', 'Student');
        $foreignLoan = LibraryLoan::create([
            'tenant_id' => $foreignTenant->id,
            'book_id' => $foreignBook->id,
            'student_id' => $foreignStudent->id,
            'issue_date' => now()->subDay()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'issued',
            'issued_by' => $foreignLibrarian->id,
        ]);

        $this->withToken(ApiToken::issue($librarian, 'library-cross-return'))
            ->postJson('/api/v1/library/loans/'.$foreignLoan->id.'/return')
            ->assertNotFound();
        $this->assertSame('issued', $foreignLoan->fresh()->status);
        $this->assertSame(0, (int) $foreignBook->fresh()->available_copies);
    }

    public function test_custom_library_deny_blocks_mutations(): void
    {
        [$tenant, $librarian] = $this->school('Denied Library School', 'librarian');
        DB::table('staff_permissions')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $librarian->id,
            'module' => 'library',
            'type' => 'deny',
            'granted_by' => $librarian->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($librarian, 'library-denied'))
            ->getJson('/api/v1/library/options')
            ->assertForbidden();
    }

    private function school(string $name, string $role): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name.' User',
            'role' => $role,
            'staff_id' => strtoupper(substr(str($name)->slug('')->toString(), 0, 6)).'-001',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        return [$tenant, $user];
    }

    private function book(int $tenantId, string $title, int $total, ?int $available = null): LibraryBook
    {
        return LibraryBook::create([
            'tenant_id' => $tenantId,
            'title' => $title,
            'author' => 'EduCore Author',
            'category' => 'science',
            'total_copies' => $total,
            'available_copies' => $available ?? $total,
            'condition' => 'good',
            'is_active' => true,
        ]);
    }

    private function student(int $tenantId, string $number, string $first, string $last): Student
    {
        return Student::create([
            'tenant_id' => $tenantId,
            'admission_number' => $number,
            'first_name' => $first,
            'last_name' => $last,
            'status' => Student::STATUS_ACTIVE,
        ]);
    }
}
