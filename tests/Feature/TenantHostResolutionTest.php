<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantHostResolution;
use App\Services\TenantHostResolver;
use App\Services\TenantUrlGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantHostResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Tenant host resolution tests require the isolated sqlite :memory: test database.');
        }

        config([
            'tenancy.central_hosts' => ['educore.test', 'localhost', '127.0.0.1'],
            'tenancy.local_base_domain' => 'educore.test',
            'tenancy.scheme' => 'http',
        ]);

        $this->rebuildSchema();
    }

    public function test_central_hosts_are_recognised(): void
    {
        $resolution = $this->resolver()->resolve('educore.test');

        $this->assertTrue($resolution->isCentral());
        $this->assertNull($resolution->tenant);
    }

    public function test_local_subdomain_resolves_by_slug(): void
    {
        $tenant = $this->tenantFixture('Bluerayy Academy', 'bluerayy');

        $resolution = $this->resolver()->resolve('BLUERAYY.educore.test.');

        $this->assertSame(TenantHostResolution::TYPE_LOCAL_SUBDOMAIN, $resolution->type);
        $this->assertTrue($resolution->tenant->is($tenant));
        $this->assertSame('bluerayy', $resolution->tenantKey);
    }

    public function test_unknown_local_subdomain_does_not_create_tenant(): void
    {
        $resolution = $this->resolver()->resolve('missing.educore.test');

        $this->assertSame(TenantHostResolution::TYPE_UNKNOWN, $resolution->type);
        $this->assertNull($resolution->tenant);
        $this->assertSame(0, Tenant::count());
    }

    public function test_verified_custom_domain_resolves_and_unverified_domain_does_not(): void
    {
        $verified = $this->tenantFixture('Verified School', 'verified-school', [
            'custom_domain' => 'school.local.test',
            'domain_verified' => true,
        ]);
        $this->tenantFixture('Unverified School', 'unverified-school', [
            'custom_domain' => 'unsafe.local.test',
            'domain_verified' => false,
        ]);

        $resolved = $this->resolver()->resolve('school.local.test');
        $blocked = $this->resolver()->resolve('unsafe.local.test');

        $this->assertSame(TenantHostResolution::TYPE_CUSTOM_DOMAIN, $resolved->type);
        $this->assertTrue($resolved->tenant->is($verified));
        $this->assertSame(TenantHostResolution::TYPE_UNKNOWN, $blocked->type);
        $this->assertNull($blocked->tenant);
    }

    public function test_strict_host_validation_rejects_unsafe_hosts(): void
    {
        $resolver = $this->resolver();

        $this->assertNull($resolver->normalizeHost('bad_host.educore.test'));
        $this->assertNull($resolver->normalizeHost('-bad.educore.test'));
        $this->assertNull($resolver->normalizeHost('bad-.educore.test'));
        $this->assertNull($resolver->validateCustomDomain('educore.test'));
        $this->assertNull($resolver->validateCustomDomain('bluerayy.educore.test'));
        $this->assertSame('portal.local.test', $resolver->validateCustomDomain('PORTAL.local.test.'));
    }

    public function test_tenant_url_generator_prefers_verified_custom_domain_then_local_subdomain(): void
    {
        $local = $this->tenantFixture('Local School', 'local-school');
        $custom = $this->tenantFixture('Custom School', 'custom-school', [
            'custom_domain' => 'school.local.test',
            'domain_verified' => true,
        ]);
        $urls = app(TenantUrlGenerator::class);

        $this->assertSame('http://local-school.educore.test/login', $urls->login($local));
        $this->assertSame('http://school.local.test/apply', $urls->apply($custom));
    }

    private function resolver(): TenantHostResolver
    {
        return app(TenantHostResolver::class);
    }

    private function tenantFixture(string $name, string $slug, array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'status' => Tenant::STATUS_ACTIVE,
            'subscription_expires_at' => now()->addYear()->toDateString(),
            'domain_verified' => false,
        ], $overrides));
    }

    private function rebuildSchema(): void
    {
        Schema::dropIfExists('tenants');

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subdomain')->nullable();
            $table->string('custom_domain')->nullable();
            $table->boolean('domain_verified')->default(false);
            $table->string('status')->default(Tenant::STATUS_PENDING);
            $table->date('subscription_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
