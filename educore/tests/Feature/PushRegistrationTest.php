<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\PushController;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PushRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Push registration tests require sqlite :memory:.');
        }

        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token', 255)->unique();
            $table->string('platform', 20)->default('android');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_canonical_and_legacy_mobile_payloads_register_and_unregister_the_device(): void
    {
        $first = User::create(['tenant_id' => 1, 'name' => 'First User', 'is_active' => true]);
        $second = User::create(['tenant_id' => 1, 'name' => 'Second User', 'is_active' => true]);
        $controller = app(PushController::class);

        $canonical = Request::create('/api/v1/push/register', 'POST', [
            'token' => 'fcm-device-token',
            'platform' => 'android',
        ]);
        $canonical->setUserResolver(fn () => $first);
        $this->assertSame(200, $controller->registerToken($canonical)->status());
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $first->id,
            'token' => 'fcm-device-token',
            'platform' => 'android',
        ]);

        $legacy = Request::create('/api/v1/devices/register', 'POST', [
            'fcm_token' => 'fcm-device-token',
            'platform' => 'android',
        ]);
        $legacy->setUserResolver(fn () => $second);
        $this->assertSame(200, $controller->registerToken($legacy)->status());
        $this->assertSame($second->id, DeviceToken::where('token', 'fcm-device-token')->value('user_id'));
        $this->assertSame(1, DeviceToken::count());

        $unregister = Request::create('/api/v1/devices/unregister', 'POST', [
            'fcm_token' => 'fcm-device-token',
        ]);
        $unregister->setUserResolver(fn () => $second);
        $this->assertSame(200, $controller->unregisterToken($unregister)->status());
        $this->assertDatabaseMissing('device_tokens', ['token' => 'fcm-device-token']);
    }
}
