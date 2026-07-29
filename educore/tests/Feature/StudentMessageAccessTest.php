<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\MessageController;
use App\Models\MessageThread;
use App\Models\MessageThreadReply;
use App\Models\Scopes\TenantContext;
use App\Models\User;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StudentMessageAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Student message tests require sqlite :memory:.');
        }

        foreach (['message_thread_replies', 'message_threads', 'students', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('role');
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('current_class_arm_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('subject');
            $table->unsignedBigInteger('initiated_by');
            $table->string('status')->default('open');
            $table->timestamps();
        });
        Schema::create('message_thread_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        TenantContext::set(1);

        $push = \Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('notifyMessageThread')->zeroOrMoreTimes();
        $this->app->instance(PushNotificationService::class, $push);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_concerned_student_can_list_open_and_reply_to_their_thread(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'tenant_id' => 1, 'name' => 'School Admin', 'role' => 'admin', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'tenant_id' => 1, 'name' => 'Concerned Student', 'role' => 'student', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('students')->insert([
            'id' => 10,
            'tenant_id' => 1,
            'user_id' => 2,
            'admission_number' => 'EDU/001',
            'first_name' => 'Concerned',
            'last_name' => 'Student',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $thread = MessageThread::create([
            'tenant_id' => 1,
            'student_id' => 10,
            'subject' => 'Attendance concern',
            'initiated_by' => 1,
        ]);
        MessageThreadReply::create([
            'tenant_id' => 1,
            'thread_id' => $thread->id,
            'sender_id' => 1,
            'body' => 'Please visit the school office.',
        ]);

        $student = User::findOrFail(2);
        $controller = app(MessageController::class);
        $request = Request::create('/api/v1/messages', 'GET');
        $request->setUserResolver(fn () => $student);

        $index = $controller->index($request)->getData(true);
        $this->assertSame($thread->id, $index['threads'][0]['id']);
        $this->assertSame(1, $index['unread_total']);

        $show = $controller->show($request, $thread)->getData(true);
        $this->assertSame('Attendance concern', $show['thread']['subject']);
        $this->assertSame(1, (int) $thread->replies()->first()->is_read);

        $replyRequest = Request::create("/api/v1/messages/{$thread->id}/reply", 'POST', [
            'body' => 'I have received the message.',
        ]);
        $replyRequest->setUserResolver(fn () => $student);
        $reply = $controller->reply($replyRequest, $thread->refresh());

        $this->assertSame(201, $reply->status());
        $this->assertDatabaseHas('message_thread_replies', [
            'thread_id' => $thread->id,
            'sender_id' => 2,
            'body' => 'I have received the message.',
        ]);
    }

    public function test_unrelated_student_cannot_open_another_students_thread(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'tenant_id' => 1, 'name' => 'School Admin', 'role' => 'admin', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'tenant_id' => 1, 'name' => 'Other Student', 'role' => 'student', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('students')->insert([
            ['id' => 10, 'tenant_id' => 1, 'user_id' => null, 'first_name' => 'Concerned', 'last_name' => 'Student', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'tenant_id' => 1, 'user_id' => 2, 'first_name' => 'Other', 'last_name' => 'Student', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $thread = MessageThread::create([
            'tenant_id' => 1,
            'student_id' => 10,
            'subject' => 'Private matter',
            'initiated_by' => 1,
        ]);

        $request = Request::create("/api/v1/messages/{$thread->id}", 'GET');
        $request->setUserResolver(fn () => User::findOrFail(2));

        $this->expectException(HttpException::class);
        app(MessageController::class)->show($request, $thread);
    }
}
