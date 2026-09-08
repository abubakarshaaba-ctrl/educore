<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\ReportCardDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ReportCardDocumentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Report document service tests require sqlite :memory:.');
        }

        foreach ([
            'termly_summaries', 'students', 'class_arms', 'class_levels', 'terms',
            'academic_sessions', 'users', 'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default(Tenant::STATUS_ACTIVE);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
        Schema::create('class_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('section')->nullable();
            $table->integer('order_index')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_arms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_level_id');
            $table->unsignedBigInteger('form_tutor_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('termly_summaries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->float('total_score')->default(0);
            $table->float('final_average')->default(0);
            $table->integer('position_in_class')->nullable();
            $table->float('class_highest_avg')->nullable();
            $table->float('class_lowest_avg')->nullable();
            $table->integer('total_students_in_class')->nullable();
            $table->integer('subjects_offered')->default(0);
            $table->integer('subjects_failed')->default(0);
            $table->json('subject_breakdown')->nullable();
            $table->string('promotion_status')->default('pending');
            $table->text('form_tutor_remark')->nullable();
            $table->text('principal_remark')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_document_uses_historical_summary_class_and_does_not_persist_generated_remark(): void
    {
        $tenant = Tenant::create([
            'name' => 'Historical PDF School',
            'slug' => 'historical-pdf-school',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $sessionId = DB::table('academic_sessions')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $termId = DB::table('terms')->insertGetId([
            'tenant_id' => $tenant->id,
            'session_id' => $sessionId,
            'name' => 'First Term',
            'start_date' => '2026-09-14',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $historicalLevel = DB::table('class_levels')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Year 10',
            'order_index' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $currentLevel = DB::table('class_levels')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Year 11',
            'order_index' => 11,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $historicalClass = DB::table('class_arms')->insertGetId([
            'tenant_id' => $tenant->id,
            'class_level_id' => $historicalLevel,
            'name' => 'A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $currentClass = DB::table('class_arms')->insertGetId([
            'tenant_id' => $tenant->id,
            'class_level_id' => $currentLevel,
            'name' => 'B',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $studentId = DB::table('students')->insertGetId([
            'tenant_id' => $tenant->id,
            'current_class_arm_id' => $currentClass,
            'admission_number' => 'PDF-HIST-01',
            'first_name' => 'Historic',
            'last_name' => 'Learner',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $summaryId = DB::table('termly_summaries')->insertGetId([
            'tenant_id' => $tenant->id,
            'student_id' => $studentId,
            'class_arm_id' => $historicalClass,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'total_score' => 82,
            'final_average' => 82,
            'position_in_class' => 1,
            'class_highest_avg' => 82,
            'class_lowest_avg' => 82,
            'total_students_in_class' => 1,
            'subjects_offered' => 1,
            'subjects_failed' => 0,
            'subject_breakdown' => json_encode([[
                'subject_id' => 501,
                'subject' => 'Biology',
                'total' => 82,
                'grade' => 'A',
                'remark' => 'Excellent',
                'is_pass' => true,
                'position' => 1,
                'class_highest' => 82,
                'class_lowest' => 82,
                'class_avg' => 82,
            ]]),
            'principal_remark' => null,
            'computed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pdfDocument = Mockery::mock();
        $pdfDocument->shouldReceive('setPaper')
            ->once()
            ->with('a4', 'portrait')
            ->andReturnSelf();
        $pdfDocument->shouldReceive('download')
            ->once()
            ->with(Mockery::on(fn (string $name): bool => str_ends_with($name, '.pdf')))
            ->andReturn(response('PDF-CONTENT', 200, ['Content-Type' => 'application/pdf']));

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use ($historicalClass, $studentId): bool {
                return $view === 'reports.pdf'
                    && (int) $data['classArm']->id === $historicalClass
                    && (int) $data['student']->id === $studentId
                    && $data['subjectRows'][0]['subject_name'] === 'Biology'
                    && (float) $data['subjectRows'][0]['total'] === 82.0
                    && !empty($data['summary']->principal_remark);
            })
            ->andReturn($pdfDocument);

        $response = app(ReportCardDocumentService::class)->download($tenant->id, $summaryId);
        $this->assertSame(200, $response->getStatusCode());

        $this->assertDatabaseHas('students', [
            'id' => $studentId,
            'current_class_arm_id' => $currentClass,
        ]);
        $this->assertDatabaseHas('termly_summaries', [
            'id' => $summaryId,
            'principal_remark' => null,
        ]);
    }

    public function test_document_summary_lookup_is_tenant_scoped(): void
    {
        $local = Tenant::create([
            'name' => 'Local PDF School',
            'slug' => 'local-pdf-school',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $foreign = Tenant::create([
            'name' => 'Foreign PDF School',
            'slug' => 'foreign-pdf-school',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(ReportCardDocumentService::class)->download($local->id, 999999 + $foreign->id);
    }
}
