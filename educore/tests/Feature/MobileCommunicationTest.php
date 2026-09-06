<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ApiToken;
use App\Models\CalendarEvent;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\MessageThread;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Mobile\MobileModuleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileCommunicationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile communication tests require sqlite :memory:.');
        }

        foreach (['device_tokens', 'guardian_student', 'guardians', 'announcement_reads', 'announcements', 'calendar_events', 'message_thread_replies', 'message_threads', 'students', 'class_arms', 'class_levels', 'api_tokens', 'users', 'tenants'] as $table) {
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
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('form_tutor_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('admission_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('guardians', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('guardian_student', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guardian_id');
            $table->unsignedBigInteger('student_id');
            $table->boolean('is_primary_contact')->default(false);
            $table->timestamps();
        });
        Schema::create('device_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token', 512)->unique();
            $table->string('platform', 20)->default('android');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('title');
            $table->text('body');
            $table->string('audience')->default('all');
            $table->string('priority')->default('normal');
            $table->date('publish_date');
            $table->date('expire_date')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
        Schema::create('announcement_reads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('announcement_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at');
            $table->timestamps();
            $table->unique(['tenant_id', 'announcement_id', 'user_id']);
        });
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('type')->default('event');
            $table->string('color')->default('#2563EB');
            $table->boolean('is_public')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('message_threads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('subject');
            $table->unsignedBigInteger('initiated_by');
            $table->string('status')->default('open');
            $table->timestamps();
        });
        Schema::create('message_thread_replies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamps();
        });
    }

    public function test_notifications_are_audience_and_tenant_scoped_with_synchronized_read_state(): void
    {
        [$tenant, $admin, $studentUser] = $this->school('Greenfield');
        [$otherTenant, $otherAdmin] = $this->school('Other School');
        $all = $this->announcement($tenant->id, $admin->id, 'All families', 'all');
        $student = $this->announcement($tenant->id, $admin->id, 'Student notice', 'students');
        $this->announcement($tenant->id, $admin->id, 'Staff only', 'staff');
        $this->announcement($otherTenant->id, $otherAdmin->id, 'Other tenant', 'students');
        $token = ApiToken::issue($studentUser, 'communications');

        $this->withToken($token)->getJson('/api/v1/notifications')
            ->assertOk()->assertJsonPath('contract_version', 1)->assertJsonPath('unread_count', 2)
            ->assertJsonCount(2, 'notifications')->assertJsonMissing(['title' => 'Staff only'])->assertJsonMissing(['title' => 'Other tenant']);

        $this->withToken($token)->postJson("/api/v1/notifications/{$all->id}/read")
            ->assertOk()->assertJsonPath('notification.is_read', true);
        $this->withToken($token)->getJson('/api/v1/notifications?status=unread')
            ->assertOk()->assertJsonCount(1, 'notifications')->assertJsonPath('notifications.0.id', $student->id);
        $this->withToken($token)->postJson('/api/v1/notifications/read-all')->assertOk()->assertJsonPath('updated', 2);
        $this->withToken($token)->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('unread_count', 0);
    }

    public function test_calendar_returns_only_authorized_public_tenant_events(): void
    {
        [$tenant, $admin, $studentUser] = $this->school('Calendar School');
        [$otherTenant, $otherAdmin] = $this->school('Other Calendar');
        CalendarEvent::create(['tenant_id' => $tenant->id, 'title' => 'Resumption', 'start_date' => '2026-09-07', 'type' => 'resumption', 'is_public' => true, 'created_by' => $admin->id]);
        CalendarEvent::create(['tenant_id' => $tenant->id, 'title' => 'Private meeting', 'start_date' => '2026-09-08', 'type' => 'pta', 'is_public' => false, 'created_by' => $admin->id]);
        CalendarEvent::create(['tenant_id' => $otherTenant->id, 'title' => 'Other event', 'start_date' => '2026-09-07', 'type' => 'event', 'is_public' => true, 'created_by' => $otherAdmin->id]);

        $this->withToken(ApiToken::issue($studentUser, 'calendar'))->getJson('/api/v1/calendar/events?from=2026-09-01&to=2026-09-30')
            ->assertOk()->assertJsonCount(1, 'events')->assertJsonPath('events.0.title', 'Resumption')
            ->assertJsonPath('events.0.deep_link.type', 'calendar_event');
    }

    public function test_messages_support_scoped_compose_reply_attachment_and_participant_access(): void
    {
        Storage::fake('local');
        [$tenant, $admin, $studentUser, $student] = $this->school('Message School');
        [, , $otherStudentUser] = $this->school('Other Message School');
        $adminToken = ApiToken::issue($admin, 'admin-messages');

        $this->withToken($adminToken)->getJson('/api/v1/messages/recipients')
            ->assertOk()->assertJsonCount(1, 'recipients')->assertJsonPath('recipients.0.student_id', $student->id);

        $response = $this->withToken($adminToken)->post('/api/v1/messages', [
            'student_id' => $student->id,
            'subject' => 'Biology materials',
            'body' => 'Please review the attached revision guide.',
            'attachment' => UploadedFile::fake()->create('revision.pdf', 32, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('thread.subject', 'Biology materials')->assertJsonPath('reply.attachment.name', 'revision.pdf');
        $threadId = $response->json('thread.id');
        $replyId = $response->json('reply.id');

        $studentToken = ApiToken::issue($studentUser, 'student-messages');
        $this->withToken($studentToken)->getJson("/api/v1/messages/{$threadId}")
            ->assertOk()->assertJsonPath('thread.unread_count', 0)->assertJsonPath('thread.replies.0.attachment.name', 'revision.pdf');
        $this->withToken($studentToken)->postJson("/api/v1/messages/{$threadId}/reply", ['body' => 'I have received it.'])
            ->assertCreated()->assertJsonPath('reply.is_me', true);
        $this->withToken($studentToken)->get("/api/v1/messages/replies/{$replyId}/attachment")->assertOk();
        $this->withToken(ApiToken::issue($otherStudentUser, 'cross-tenant'))->getJson("/api/v1/messages/{$threadId}")->assertNotFound();
        $this->assertSame(2, MessageThread::withoutTenantScope()->findOrFail($threadId)->replies()->withoutGlobalScopes()->count());
    }

    public function test_student_and_parent_mobile_catalogues_expose_communication_destinations(): void
    {
        [, , $student] = $this->school('Module School');
        $studentKeys = collect(app(MobileModuleService::class)->forUser($student))->pluck('key');

        $this->assertTrue($studentKeys->contains('student.messages'));
        $this->assertTrue($studentKeys->contains('student.notifications'));
        $this->assertTrue($studentKeys->contains('student.calendar'));
        $this->assertSame($studentKeys->count(), $studentKeys->unique()->count());
    }

    private function school(string $name): array
    {
        $tenant = Tenant::create(['name' => $name, 'slug' => str($name)->slug().'-'.uniqid(), 'status' => 'active']);
        $admin = User::create(['tenant_id' => $tenant->id, 'name' => $name.' Admin', 'role' => 'admin', 'is_active' => true, 'employment_status' => User::STAFF_STATUS_ACTIVE]);
        $studentUser = User::create(['tenant_id' => $tenant->id, 'name' => $name.' Student', 'role' => 'student', 'is_active' => true]);
        $level = ClassLevel::create(['tenant_id' => $tenant->id, 'name' => 'JSS 1', 'section' => 'junior', 'order_index' => 1]);
        $arm = ClassArm::create(['tenant_id' => $tenant->id, 'class_level_id' => $level->id, 'name' => 'A']);
        $student = Student::create(['tenant_id' => $tenant->id, 'user_id' => $studentUser->id, 'admission_number' => 'STU-'.uniqid(), 'first_name' => 'Amina', 'last_name' => 'Bello', 'current_class_arm_id' => $arm->id, 'status' => Student::STATUS_ACTIVE]);

        return [$tenant, $admin, $studentUser, $student];
    }

    private function announcement(int $tenantId, int $authorId, string $title, string $audience): Announcement
    {
        return Announcement::create([
            'tenant_id' => $tenantId, 'title' => $title, 'body' => $title.' body', 'audience' => $audience,
            'priority' => 'normal', 'publish_date' => today(), 'is_published' => true, 'created_by' => $authorId,
        ]);
    }
}
