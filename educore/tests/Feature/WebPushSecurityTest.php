<?php

namespace Tests\Feature;

use App\Http\Controllers\PushNotificationController;
use App\Models\PushSubscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WebPushSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Web push security tests require sqlite :memory:.');
        }

        foreach (['notification_queue', 'push_subscriptions', 'users', 'tenants'] as $table) {
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
            $table->boolean('is_migration_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('endpoint')->unique();
            $table->text('p256dh_key');
            $table->text('auth_key');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('notification_queue', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('channel');
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('gateway')->default('web_push');
            $table->string('status')->default('pending');
            $table->integer('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_user_cannot_take_over_or_unsubscribe_another_users_endpoint(): void
    {
        $tenant = $this->tenant('Push School');
        $owner = $this->user($tenant, 'Owner', 'subject_teacher');
        $other = $this->user($tenant, 'Other', 'subject_teacher');
        $controller = app(PushNotificationController::class);

        $this->actingAs($owner);
        $ownerRequest = Request::create('/push/subscribe', 'POST', [
            'endpoint' => 'https://push.example.test/device-1',
            'p256dh_key' => 'owner-key',
            'auth_key' => 'owner-auth',
        ]);
        $this->assertSame(200, $controller->subscribe($ownerRequest)->status());

        $this->actingAs($other);
        try {
            $controller->subscribe(Request::create('/push/subscribe', 'POST', [
                'endpoint' => 'https://push.example.test/device-1',
                'p256dh_key' => 'other-key',
                'auth_key' => 'other-auth',
            ]));
            $this->fail('Expected endpoint takeover to be rejected.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('endpoint', $error->errors());
        }

        $controller->unsubscribe(Request::create('/push/unsubscribe', 'POST', [
            'endpoint' => 'https://push.example.test/device-1',
        ]));

        $this->assertDatabaseHas('push_subscriptions', [
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'endpoint' => 'https://push.example.test/device-1',
            'p256dh_key' => 'owner-key',
            'is_active' => true,
        ]);

        $this->actingAs($owner);
        $controller->unsubscribe(Request::create('/push/unsubscribe', 'POST', [
            'endpoint' => 'https://push.example.test/device-1',
        ]));
        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => 'https://push.example.test/device-1',
        ]);
    }

    public function test_read_only_notification_role_cannot_broadcast_push(): void
    {
        $tenant = $this->tenant('Read Only School');
        $teacher = $this->user($tenant, 'Read Only Teacher', 'subject_teacher');
        $this->actingAs($teacher);

        try {
            app(PushNotificationController::class)->broadcast(Request::create('/push/broadcast', 'POST', [
                'title' => 'Not allowed',
                'body' => 'This must never be queued.',
            ]));
            $this->fail('Expected notification-view-only account to be forbidden.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }

        $this->assertDatabaseCount('notification_queue', 0);
    }

    public function test_notifications_send_role_can_broadcast_only_to_own_school(): void
    {
        $tenant = $this->tenant('Sender School');
        $otherTenant = $this->tenant('Other School');
        $sender = $this->user($tenant, 'Information Officer', 'information_officer');
        $recipient = $this->user($tenant, 'Recipient', 'subject_teacher');
        $foreignRecipient = $this->user($otherTenant, 'Foreign Recipient', 'subject_teacher');

        PushSubscription::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $recipient->id,
            'endpoint' => 'https://push.example.test/own',
            'p256dh_key' => 'own-key',
            'auth_key' => 'own-auth',
            'is_active' => true,
        ]);
        PushSubscription::withoutTenantScope()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $foreignRecipient->id,
            'endpoint' => 'https://push.example.test/foreign',
            'p256dh_key' => 'foreign-key',
            'auth_key' => 'foreign-auth',
            'is_active' => true,
        ]);

        $this->actingAs($sender);
        app(PushNotificationController::class)->broadcast(Request::create('/push/broadcast', 'POST', [
            'title' => 'School notice',
            'body' => 'Own school recipients only.',
        ]));

        $this->assertDatabaseHas('notification_queue', [
            'tenant_id' => $tenant->id,
            'recipient' => 'https://push.example.test/own',
            'subject' => 'School notice',
        ]);
        $this->assertDatabaseMissing('notification_queue', [
            'recipient' => 'https://push.example.test/foreign',
        ]);
    }

    private function tenant(string $name): Tenant
    {
        return Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);
    }

    private function user(Tenant $tenant, string $name, string $role): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => str($name)->slug('.').'.'.uniqid().'@example.test',
            'role' => $role,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
    }
}
