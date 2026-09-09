<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobilePlatformSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform settings tests require sqlite :memory:.');
        }

        foreach (['audit_logs', 'platform_settings', 'api_tokens', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('status')->default(Tenant::STATUS_ACTIVE);
            $table->timestamps(); $table->softDeletes();
        });
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

    public function test_tenant_admin_cannot_update_platform_settings_or_gateways(): void
    {
        $tenant = Tenant::create(['name' => 'Restricted School', 'slug' => 'restricted-school', 'status' => Tenant::STATUS_ACTIVE]);
        $admin = $this->user('Restricted Admin', false, $tenant->id);
        $token = ApiToken::issue($admin, 'settings-deny');

        $this->withToken($token)->putJson('/api/v1/platform/gateways/paystack', [
            'public_key' => 'pk_test_ABC123456',
            'secret_key' => 'sk_test_SECRET123456',
            'live' => false,
            'reason' => 'Unauthorized gateway mutation',
        ])->assertForbidden();
    }

    public function test_gateway_secret_is_encrypted_write_only_and_never_appears_in_audit_payload(): void
    {
        $super = $this->user('Gateway Admin', true, null);
        $token = ApiToken::issue($super, 'gateway-write');
        $secret = 'sk_test_SUPERSECRET123456789';

        $response = $this->withToken($token)->putJson('/api/v1/platform/gateways/paystack', [
            'public_key' => 'pk_test_PUBLIC123456789',
            'secret_key' => $secret,
            'live' => false,
            'reason' => 'Rotate Paystack credentials safely',
        ])->assertOk()->assertJsonPath('provider', 'paystack')->assertJsonPath('secret_replaced', true);

        $stored = DB::table('platform_settings')->where('key', 'paystack_secret_key')->value('value');
        $this->assertNotSame($secret, $stored);
        $this->assertSame($secret, PlatformSetting::valueFor('paystack_secret_key'));

        $json = json_encode($response->json(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($secret, $json);
        $audit = DB::table('audit_logs')->where('action', 'platform.gateway.updated')->first();
        $this->assertNotNull($audit);
        $this->assertStringNotContainsString($secret, (string) $audit->new_values);
    }

    public function test_blank_gateway_secret_preserves_existing_encrypted_secret(): void
    {
        $super = $this->user('Gateway Preserve Admin', true, null);
        PlatformSetting::setValue('paystack_secret_key', 'sk_test_EXISTING123456', 'encrypted', 'payments', 'Paystack Secret');
        $before = DB::table('platform_settings')->where('key', 'paystack_secret_key')->value('value');

        $this->withToken(ApiToken::issue($super, 'gateway-preserve'))
            ->putJson('/api/v1/platform/gateways/paystack', [
                'public_key' => 'pk_test_UPDATED123456',
                'secret_key' => '',
                'live' => false,
                'reason' => 'Update public identifier only',
            ])
            ->assertOk()
            ->assertJsonPath('secret_replaced', false);

        $after = DB::table('platform_settings')->where('key', 'paystack_secret_key')->value('value');
        $this->assertSame($before, $after);
        $this->assertSame('sk_test_EXISTING123456', PlatformSetting::valueFor('paystack_secret_key'));
    }

    public function test_secret_key_forces_encrypted_storage_even_when_caller_requests_string_type(): void
    {
        $secret = 'sk_test_FORCEDENCRYPTION123456';
        PlatformSetting::setValue('paystack_secret_key', $secret, 'string', 'payments', 'Paystack Secret');

        $row = DB::table('platform_settings')->where('key', 'paystack_secret_key')->first();
        $this->assertNotNull($row);
        $this->assertSame('encrypted', $row->type);
        $this->assertNotSame($secret, $row->value);
        $this->assertSame($secret, PlatformSetting::valueFor('paystack_secret_key'));
    }

    public function test_corrupted_encrypted_secret_fails_closed_instead_of_returning_raw_storage_value(): void
    {
        DB::table('platform_settings')->insert([
            'key' => 'paystack_secret_key',
            'value' => 'not-valid-laravel-ciphertext',
            'type' => 'encrypted',
            'group' => 'payments',
            'label' => 'Paystack Secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(PlatformSetting::valueFor('paystack_secret_key'));
    }

    public function test_unmigrated_plaintext_secret_row_fails_closed(): void
    {
        DB::table('platform_settings')->insert([
            'key' => 'monnify_secret_key',
            'value' => 'legacy-plaintext-secret',
            'type' => 'string',
            'group' => 'payments',
            'label' => 'Monnify Secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(PlatformSetting::valueFor('monnify_secret_key'));
    }

    public function test_operational_settings_are_whitelisted_validated_and_audited(): void
    {
        $super = $this->user('Operations Admin', true, null);
        $settings = [
            'platform_name' => 'EduCore',
            'support_email' => 'support@example.test',
            'support_phone' => '07000000000',
            'support_whatsapp' => '07000000000',
            'support_website' => 'https://educoreng.online',
            'office_address' => 'Abuja, Nigeria',
            'grace_period_days' => 7,
            'bank_transfer_bank_name' => null,
            'bank_transfer_account_name' => null,
            'bank_transfer_account_number' => null,
            'default_sms_gateway' => 'termii',
            'sms_sender_id' => 'EduCore',
            'maintenance_mode' => false,
        ];

        $this->withToken(ApiToken::issue($super, 'settings-write'))
            ->putJson('/api/v1/platform/settings', [
                'settings' => $settings,
                'reason' => 'Update approved platform operations settings',
            ])
            ->assertOk()
            ->assertJsonFragment(['platform_name']);

        $this->assertSame('EduCore', PlatformSetting::valueFor('platform_name'));
        $this->assertSame(7, PlatformSetting::valueFor('grace_period_days'));
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $super->id,
            'action' => 'platform.settings.updated',
            'reason' => 'Update approved platform operations settings',
        ]);
    }

    private function user(string $name, bool $super, ?int $tenantId): User
    {
        return User::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => str($name)->slug().'.'.uniqid().'@example.test',
            'role' => $super ? 'super_admin' : 'admin',
            'is_super_admin' => $super,
            'is_active' => true,
            'employment_status' => $super ? null : User::STAFF_STATUS_ACTIVE,
        ]);
    }
}
