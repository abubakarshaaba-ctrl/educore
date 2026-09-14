<?php

namespace Tests\Feature;

use App\Http\Controllers\AcademicRepositoryKnowledgeController;
use App\Http\Controllers\Api\MobileAcademicKnowledgeController;
use Illuminate\Http\Request;
use Tests\TestCase;

class AcademicKnowledgeRouteRegistrationTest extends TestCase
{
    public function test_web_knowledge_index_is_not_captured_by_the_resource_wildcard(): void
    {
        $route = app('router')->getRoutes()->match(
            Request::create('/academic-repository/knowledge', 'GET')
        );

        $this->assertSame('academic-repository.knowledge.index', $route->getName());
        $this->assertSame(
            AcademicRepositoryKnowledgeController::class.'@index',
            $route->getActionName()
        );
        $this->assertCount(1, $this->getRoutesFor('academic-repository/knowledge', 'GET'));
    }

    public function test_mobile_knowledge_index_is_registered_in_the_v1_api(): void
    {
        $route = app('router')->getRoutes()->match(
            Request::create('/api/v1/academic-repository/knowledge', 'GET')
        );

        $this->assertSame(
            MobileAcademicKnowledgeController::class.'@index',
            $route->getActionName()
        );
        $this->assertCount(1, $this->getRoutesFor('api/v1/academic-repository/knowledge', 'GET'));
    }

    private function getRoutesFor(string $uri, string $method)
    {
        return collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->uri() === $uri && in_array($method, $route->methods(), true));
    }
}
