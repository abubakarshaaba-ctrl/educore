<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\ReportCardPublicationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReportCardPublicationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Report publication service tests require sqlite :memory:.');
        }

        foreach (['audit_logs', 'report_card_publications', 'termly_summaries', 'users', 'tenants'] as $table) {
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
        Schema::create('report_card_publications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->unsignedBigInteger('term_id');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'class_arm_id', 'term_id']);
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function test_publish_and_unpublish_are_audited_once_and_repeated_transitions_are_rejected(): void
    {
        [$tenant, $actor] = $this->school('Publication School');
        $classArmId = 41;
        $termId = 71;
        $this->summary($tenant->id, $classArmId, $termId);
        $service = app(ReportCardPublicationService::class);

        $published = $service->publish(
            $tenant->id,
            $classArmId,
            $termId,
            $actor,
            'Approved results.',
        );

        $this->assertSame('published', $published['publication']->status);
        $this->assertSame(0, $published['guardians_notified']);
        $this->assertDatabaseHas('report_card_publications', [
            'tenant_id' => $tenant->id,
            'class_arm_id' => $classArmId,
            'term_id' => $termId,
            'status' => 'published',
            'published_by' => $actor->id,
            'note' => 'Approved results.',
        ]);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'report_cards.published')->count());

        try {
            $service->publish($tenant->id, $classArmId, $termId, $actor);
            $this->fail('A duplicate publication should have been rejected.');
        } catch (ValidationException) {
            $this->assertSame(1, DB::table('audit_logs')->where('action', 'report_cards.published')->count());
        }

        $draft = $service->unpublish($tenant->id, $classArmId, $termId, $actor);
        $this->assertSame('draft', $draft->status);
        $this->assertNotNull($draft->archived_at);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'report_cards.unpublished')->count());

        try {
            $service->unpublish($tenant->id, $classArmId, $termId, $actor);
            $this->fail('A duplicate unpublish should have been rejected.');
        } catch (ValidationException) {
            $this->assertSame(1, DB::table('audit_logs')->where('action', 'report_cards.unpublished')->count());
        }
    }

    public function test_publish_cannot_use_another_tenants_computed_summary(): void
    {
        [$localTenant, $localActor] = $this->school('Local Publication School');
        [$foreignTenant] = $this->school('Foreign Publication School');
        $classArmId = 55;
        $termId = 88;
        $this->summary($foreignTenant->id, $classArmId, $termId);

        $this->expectException(ValidationException::class);
        app(ReportCardPublicationService::class)->publish(
            $localTenant->id,
            $classArmId,
            $termId,
            $localActor,
        );
    }

    private function school(string $name): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name.' Admin',
            'email' => str($name)->slug().'.'.uniqid().'@example.test',
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        return [$tenant, $user];
    }

    private function summary(int $tenantId, int $classArmId, int $termId): void
    {
        DB::table('termly_summaries')->insert([
            'tenant_id' => $tenantId,
            'student_id' => random_int(1000, 9999),
            'class_arm_id' => $classArmId,
            'term_id' => $termId,
            'session_id' => 2026,
            'total_score' => 400,
            'final_average' => 80,
            'position_in_class' => 1,
            'total_students_in_class' => 1,
            'subjects_offered' => 5,
            'subjects_failed' => 0,
            'subject_breakdown' => json_encode([]),
            'promotion_status' => 'pending',
            'computed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
