<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * UI rendering tests for global authentication pages.
 *
 * Verifies that the authentication pages:
 * - render with correct structure
 * - contain CSRF tokens
 * - use correct autocomplete attributes
 * - contain no external CDN references
 * - maintain correct role wording per portal
 * - do not expose global login with tenant branding
 */
class AuthenticationPageDesignTest extends TestCase
{
    // ──────────────────────────────────────────────────────────
    // Global login
    // ──────────────────────────────────────────────────────────

    public function test_global_login_renders_successfully(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_global_login_contains_csrf_token(): void
    {
        $response = $this->get('/login');
        $response->assertOk();

        // @csrf renders as a hidden input with name="_token"
        $response->assertSee('_token', false);
    }

    public function test_global_login_uses_shared_auth_layout(): void
    {
        $response = $this->get('/login');
        $response->assertOk();

        // Shared layout injects the ec-auth class and the password-toggle script marker
        $response->assertSee('ec-auth', false);
        $response->assertSee('data-ec-eye', false);
    }

    public function test_global_login_heading_identifies_educore(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to EduCore');
    }

    public function test_global_login_password_field_has_correct_autocomplete(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('autocomplete="current-password"', false);
    }

    public function test_global_login_username_field_has_correct_autocomplete(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('autocomplete="username"', false);
    }

    public function test_global_login_contains_no_external_cdn_references(): void
    {
        $content = $this->get('/login')->getContent();

        $this->assertStringNotContainsString('fonts.googleapis.com', $content);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $content);
        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $content);
        $this->assertStringNotContainsString('unpkg.com', $content);
    }

    public function test_global_login_contains_no_tenant_branding(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertDontSee('tenant_id', false);
        $response->assertDontSee('school_id', false);
        // The ec-school-name ELEMENT should not appear (CSS definition may be present but no HTML element)
        $this->assertStringNotContainsString('class="ec-school-name"', $response->getContent());
    }

    public function test_global_login_password_toggle_has_accessible_label(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('aria-label', false);
        $response->assertSee('aria-pressed', false);
    }

    // ──────────────────────────────────────────────────────────
    // Parent portal login
    // ──────────────────────────────────────────────────────────

    public function test_parent_login_renders_successfully(): void
    {
        $this->get('/portal/login')->assertOk();
    }

    public function test_parent_login_uses_parent_role_wording(): void
    {
        $this->get('/portal/login')
            ->assertOk()
            ->assertSee('Parent Portal');
    }

    public function test_parent_login_contains_csrf(): void
    {
        $this->get('/portal/login')
            ->assertOk()
            ->assertSee('_token', false);
    }

    public function test_parent_login_contains_no_external_cdn_references(): void
    {
        $content = $this->get('/portal/login')->getContent();
        $this->assertStringNotContainsString('fonts.googleapis.com', $content);
        $this->assertStringNotContainsString('cdn.', $content);
    }

    public function test_parent_login_contains_no_gold_colour(): void
    {
        $content = $this->get('/portal/login')->getContent();
        // D79A21 was the old gold colour — must not appear
        $this->assertStringNotContainsString('D79A21', strtoupper($content));
        $this->assertStringNotContainsString('d79a21', $content);
    }

    public function test_parent_login_has_email_autocomplete(): void
    {
        $this->get('/portal/login')
            ->assertOk()
            ->assertSee('autocomplete="email"', false);
    }

    public function test_parent_login_is_separated_from_staff_wording(): void
    {
        $response = $this->get('/portal/login');
        $response->assertOk();
        $response->assertDontSee('Staff and Administrator Login');
        $response->assertDontSee('staff ID', false);
    }

    // ──────────────────────────────────────────────────────────
    // Agent portal login
    // ──────────────────────────────────────────────────────────

    public function test_agent_login_renders_successfully(): void
    {
        $this->get('/agent/portal/login')->assertOk();
    }

    public function test_agent_login_uses_agent_role_wording(): void
    {
        $this->get('/agent/portal/login')
            ->assertOk()
            ->assertSee('Agent Portal');
    }

    public function test_agent_login_contains_csrf(): void
    {
        $this->get('/agent/portal/login')
            ->assertOk()
            ->assertSee('_token', false);
    }

    public function test_agent_login_contains_no_external_cdn_references(): void
    {
        $content = $this->get('/agent/portal/login')->getContent();
        $this->assertStringNotContainsString('fonts.googleapis.com', $content);
        $this->assertStringNotContainsString('cdn.', $content);
    }

    public function test_agent_login_uses_shared_auth_layout(): void
    {
        $this->get('/agent/portal/login')
            ->assertOk()
            ->assertSee('ec-auth', false);
    }

    public function test_agent_login_is_separated_from_parent_wording(): void
    {
        $this->get('/agent/portal/login')
            ->assertOk()
            ->assertDontSee('Parent Portal');
    }

    // ──────────────────────────────────────────────────────────
    // Validation / status messages
    // ──────────────────────────────────────────────────────────

    public function test_global_login_validation_errors_render_with_role_alert(): void
    {
        $this->post('/login', ['login_id' => '', 'password' => ''])
            ->assertSessionHasErrors();

        $response = $this->withSession(['errors' => session('errors')])->get('/login');
        // role="alert" is produced by x-auth.alert component
        $response->assertSee('role="alert"', false);
    }
}
