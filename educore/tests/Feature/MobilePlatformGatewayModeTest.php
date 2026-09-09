<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformGatewayModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform gateway tests require sqlite :memory:.');
        }

        foreach (['audit_logs', 'platform_settings', 'api_tokens', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->string('name'); $table->string('email')->nullable()->unique();
            $table->string('password')->nullable(); $table->string('role')->nullable(); $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true); $table->string('employment_status')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('name'); $table->string('token', 64)->unique();
            $table->string('device')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->id(); $table->string('key')->unique(); $table->text('value')->nullable(); $table->string('type')->default('string');
            $table->string('group')->default('general'); $table->string('label')->nullable(); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('tenant_id')->nullable(); $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('auditable_type')->nullable(); $table->unsignedBigInteger('auditable_id')->nullable(); $table->string('action');
            $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->text('reason')->nullable();
            $table->string('ip_address')->nullable(); $table->text('user_agent')->nullable(); $table->timestamps();
        });
    }

    public function test_test_credentials_cannot_be_switched_to_live_without_live_replacements(): void
    {
        $super = $this->superAdmin('Paystack Test Mode Admin');
        PlatformSetting::setValue('paystack_public_key', 'pk_test_PUBLIC123456', 'string', 'payments');
        PlatformSetting::setValue('paystack_secret_key', 'sk_test_SECRET123456', 'encrypted', 'payments');
        PlatformSetting::setValue('paystack_is_live', false, 'boolean', 'payments');

        $this->withToken(ApiToken::issue($super, 'paystack-test-to-live'))
            ->putJson('/api/v1/platform/gateways/paystack', [
                'public_key' => '',
                'secret_key' => '',
                'live' => true,
                'reason' => 'Attempt unsafe production mode switch',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['public_key']);

        $this->assertFalse((bool) PlatformSetting::valueFor('paystack_is_live'));
    }

    public function test_live_credentials_cannot_be_switched_to_test_without_test_replacements(): void
    {
        $super = $this->superAdmin('Paystack Live Mode Admin');
        PlatformSetting::setValue('paystack_public_key', 'pk_live_PUBLIC123456', 'string', 'payments');
        PlatformSetting::setValue('paystack_secret_key', 'sk_live_SECRET123456', 'encrypted', 'payments');
        PlatformSetting::setValue('paystack_is_live', true, 'boolean', 'payments');

        $this->withToken(ApiToken::issue($super, 'paystack-live-to-test'))
            ->putJson('/api/v1/platform/gateways/paystack', [
                'public_key' => '',
                'secret_key' => '',
                'live' => false,
                'reason' => 'Attempt unsafe sandbox mode switch',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['public_key']);

        $this->assertTrue((bool) PlatformSetting::valueFor('paystack_is_live'));
    }

    public function test_matching_live_replacements_allow_live_mode(): void
    {
        $super = $this->superAdmin('Paystack Safe Live Admin');

        $this->withToken(ApiToken::issue($super, 'paystack-safe-live'))
            ->putJson('/api/v1/platform/gateways/paystack', [
                'public_key' => 'pk_live_PUBLIC123456',
                'secret_key' => 'sk_live_SECRET123456',
                'live' => true,
                'reason' => 'Configure matching production credentials',
            ])
            ->assertOk()
            ->assertJsonPath('live', true)
            ->assertJsonPath('secret_replaced', true);

        $this->assertTrue((bool) PlatformSetting::valueFor('paystack_is_live'));
        $this->assertSame('pk_live_PUBLIC123456', PlatformSetting::valueFor('paystack_public_key'));
        $this->assertSame('sk_live_SECRET123456', PlatformSetting::valueFor('paystack_secret_key'));
    }

    private function superAdmin(string $name): User
    {
        return User::create([
            'tenant_id' => null,
            'name' => $name,
            'email' => str($name)->slug().'.'.uniqid().'@example.test',
            'role' => 'super_admin',
            'is_super_admin' => true,
            'is_active' => true,
        ]);
    }
}
