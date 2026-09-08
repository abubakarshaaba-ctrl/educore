<?php

namespace Tests\Feature;

use App\Http\Controllers\SelfDeployController;
use Tests\TestCase;

class SelfWorkflowValidationControllerTest extends TestCase
{
    public function test_android_validation_requires_the_deploy_token(): void
    {
        $this->getJson('/deploy/validate-android?ref=mobile-overhaul')
            ->assertForbidden();
    }

    public function test_android_validation_rejects_unsafe_repository_refs_before_network_access(): void
    {
        $token = SelfDeployController::derivedToken();

        $this->getJson('/deploy/validate-android?token='.urlencode($token).'&ref=../master')
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'Invalid repository ref.');
    }

    public function test_android_validation_route_is_registered(): void
    {
        $route = app('router')->getRoutes()->getByName('deploy.validate-android');

        $this->assertNotNull($route);
        $this->assertSame('deploy/validate-android', $route->uri());
        $this->assertContains('GET', $route->methods());
    }
}
