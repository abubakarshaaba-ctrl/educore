<?php

namespace Tests\Feature;

use App\Models\SchoolSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * UI rendering tests for tenant authentication pages.
 *
 * Uses an in-memory SQLite schema to isolate tenant data.
 * Tests verify that tenant login, forgot-password, reset-password,
 * unavailable, and account-status pages render correctly with safe
 * branding, CSRF protection, correct wording, and no hidden
 * tenant identity fields.
 */
class TenantAuthenticationPageDesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (
            config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
        ) {
            $this->markTestSkipped(
                'Tenant auth design tests require the isolated sqlite :memory: test database.'
            );
        }

        $this->rebuildSchema();
    }

    // ──────────────────────────────────────────────────────────
    // Tenant login
    // ──────────────────────────────────────────────────────────

    public function test_tenant_slug_login_renders_successfully(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/login')
            ->assertOk()
            ->assertSee('Staff and Administrator Login');
    }

    public function test_tenant_login_contains_csrf(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/login')
            ->assertOk()
            ->assertSee('_token', false);
    }

    public function test_tenant_login_contains_no_hidden_tenant_id_field(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $response = $this->get('/school/greenfield-academy/login');
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringNotContainsString('name="tenant_id"', $content);
        $this->assertStringNotContainsString('name="school_id"', $content);
    }

    public function test_tenant_login_displays_school_name(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/login')
            ->assertOk()
            ->assertSee('Greenfield Academy');
    }

    public function test_tenant_login_shows_school_branding_colours(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy', [
            'theme_primary' => '#1A3A5C',
        ]);

        $this->get('/school/greenfield-academy/login')
            ->assertOk()
            ->assertSee('#1A3A5C', false);
    }

    public function test_tenant_login_uses_shared_auth_layout(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/login')
            ->assertOk()
            ->assertSee('ec-auth', false);
    }

    public function test_tenant_login_contains_no_external_cdn_references(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $content = $this->get('/school/greenfield-academy/login')->getContent();
        $this->assertStringNotContainsString('fonts.googleapis.com', $content);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $content);
    }

    public function test_tenant_login_password_field_has_correct_autocomplete(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/login')
            ->assertOk()
            ->assertSee('autocomplete="current-password"', false);
    }

    public function test_tenant_login_password_toggle_has_accessible_label(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/login')
            ->assertOk()
            ->assertSee('aria-label', false)
            ->assertSee('data-ec-eye', false);
    }

    public function test_tenant_login_form_action_matches_expected_route(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/login')
            ->assertOk()
            ->assertSee('/school/greenfield-academy/login', false);
    }

    // ──────────────────────────────────────────────────────────
    // Branding safety
    // ──────────────────────────────────────────────────────────

    public function test_unsafe_school_name_is_html_escaped(): void
    {
        $this->tenantFixture('<script>xss()</script>', 'xss-school');

        $response = $this->get('/school/xss-school/login');
        $response->assertOk();

        $content = $response->getContent();

        // The raw <script> tag must never appear as executable HTML
        $this->assertStringNotContainsString('<script>xss()</script>', $content);

        // The name must appear in HTML-escaped form somewhere (title or heading)
        // Blade {{ }} escaping produces &lt;script&gt; for <script>
        $this->assertTrue(
            str_contains($content, '&lt;script&gt;')
            || str_contains($content, '&amp;lt;script&amp;gt;'),
            'School name should be HTML-escaped in the response.'
        );
    }

    public function test_unsafe_colour_falls_back_to_default(): void
    {
        $this->tenantFixture('Safe School', 'safe-school', [
            'theme_primary' => 'javascript:alert(1)',
        ]);

        $response = $this->get('/school/safe-school/login');
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringNotContainsString('javascript:', $content);
        $this->assertStringContainsString('#071E45', $content);
    }

    public function test_unsafe_motto_is_html_escaped(): void
    {
        $tenant = $this->tenantFixture('Motto School', 'motto-school');
        $this->setting($tenant, 'motto', '<img src=x onerror=alert(1)>');

        $response = $this->get('/school/motto-school/login');
        $response->assertOk();
        $response->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    // ──────────────────────────────────────────────────────────
    // Forgot-password page
    // ──────────────────────────────────────────────────────────

    public function test_tenant_forgot_password_renders_successfully(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/forgot-password')
            ->assertOk()
            ->assertSee('Reset password');
    }

    public function test_tenant_forgot_password_contains_csrf(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/forgot-password')
            ->assertOk()
            ->assertSee('_token', false);
    }

    public function test_tenant_forgot_password_email_field_has_correct_autocomplete(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/forgot-password')
            ->assertOk()
            ->assertSee('autocomplete="email"', false);
    }

    public function test_tenant_forgot_password_has_back_to_login_link(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/forgot-password')
            ->assertOk()
            ->assertSee('Back to login');
    }

    // ──────────────────────────────────────────────────────────
    // Reset-password page
    // ──────────────────────────────────────────────────────────

    public function test_tenant_reset_password_renders_successfully(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/reset-password/some-token?email=staff@test.ng')
            ->assertOk()
            ->assertSee('Set new password');
    }

    public function test_tenant_reset_password_contains_csrf(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/reset-password/some-token?email=staff@test.ng')
            ->assertOk()
            ->assertSee('_token', false);
    }

    public function test_tenant_reset_password_has_password_toggles(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/reset-password/some-token?email=staff@test.ng')
            ->assertOk()
            ->assertSee('data-ec-eye', false);
    }

    public function test_tenant_reset_new_password_field_has_correct_autocomplete(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $this->get('/school/greenfield-academy/reset-password/some-token?email=staff@test.ng')
            ->assertOk()
            ->assertSee('autocomplete="new-password"', false);
    }

    public function test_tenant_reset_password_token_not_visible_in_plain_text(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        $response = $this->get('/school/greenfield-academy/reset-password/SECRET-TOKEN?email=staff@test.ng');
        $response->assertOk();

        // Token should only appear in a hidden input, not visible text
        $content = $response->getContent();
        $visibleCount = substr_count(strip_tags($content), 'SECRET-TOKEN');
        $this->assertSame(0, $visibleCount, 'Token must not appear in visible page text.');
    }

    // ──────────────────────────────────────────────────────────
    // Unavailable page
    // ──────────────────────────────────────────────────────────

    public function test_unavailable_page_renders_safely(): void
    {
        // Without a tenant, the unavailable page should still render with a controlled message
        $this->get('/school/does-not-exist')
            ->assertStatus(404)
            ->assertSee('School Portal Unavailable');
    }

    // ──────────────────────────────────────────────────────────
    // Shared layout consistency
    // ──────────────────────────────────────────────────────────

    public function test_all_tenant_login_pages_use_shared_layout(): void
    {
        $this->tenantFixture('Greenfield Academy', 'greenfield-academy');

        foreach ([
            '/school/greenfield-academy/login',
            '/school/greenfield-academy/forgot-password',
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('ec-auth', false)
                ->assertSee('ec-auth__brand', false);
        }
    }

    // ──────────────────────────────────────────────────────────
    // Fixtures & helpers
    // ──────────────────────────────────────────────────────────

    private function tenantFixture(string $name, string $slug, array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name'   => $name,
            'slug'   => $slug,
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
            'theme_primary' => '#071E45',
            'theme_accent'  => '#0D9488',
        ], $overrides));
    }

    private function setting(Tenant $tenant, string $key, string $value): void
    {
        SchoolSetting::withoutTenantScope()->create([
            'tenant_id' => $tenant->id,
            'key'       => $key,
            'value'     => $value,
            'group'     => 'general',
        ]);
    }

    private function rebuildSchema(): void
    {
        foreach (['school_settings', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subdomain')->nullable()->unique();
            $table->string('logo_path')->nullable();
            $table->string('motto')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default(Tenant::STATUS_PENDING);
            $table->date('subscription_expires_at')->nullable();
            $table->string('theme_primary', 20)->nullable();
            $table->string('theme_accent', 20)->nullable();
            $table->string('theme_sidebar', 20)->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->string('custom_domain')->nullable();
            $table->boolean('domain_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->string('staff_id')->nullable()->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_super_admin')->default(false);
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('employment_status', 40)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
        });
    }
}
