<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CurriculumRepositoryUiTest extends TestCase
{
    public function test_repository_workflows_have_three_dedicated_pages(): void
    {
        $indexRoute = Route::getRoutes()->getByName('super.curriculum-sources.index');
        $importRoute = Route::getRoutes()->getByName('super.curriculum-sources.create');
        $topicsRoute = Route::getRoutes()->getByName('super.curriculum-sources.topics.index');
        $storeRoute = Route::getRoutes()->getByName('super.curriculum-sources.store');

        $this->assertNotNull($indexRoute);
        $this->assertNotNull($importRoute);
        $this->assertNotNull($topicsRoute);
        $this->assertNotNull($storeRoute);
        $this->assertSame('super/curriculum-sources', $indexRoute->uri());
        $this->assertSame('super/curriculum-sources/import', $importRoute->uri());
        $this->assertSame('super/curriculum-sources/topics', $topicsRoute->uri());
        $this->assertSame(['GET', 'HEAD'], $importRoute->methods());
        $this->assertSame(['GET', 'HEAD'], $topicsRoute->methods());
        $this->assertStringEndsWith('@create', $importRoute->getActionName());
        $this->assertStringEndsWith('@topics', $topicsRoute->getActionName());
    }

    public function test_resources_import_and_topic_mapping_views_are_separate(): void
    {
        $repository = file_get_contents(resource_path('views/curriculum-sources/index.blade.php'));
        $import = file_get_contents(resource_path('views/curriculum-sources/import.blade.php'));
        $topics = file_get_contents(resource_path('views/curriculum-sources/topics.blade.php'));

        $this->assertStringContainsString("route('super.curriculum-sources.create')", $repository);
        $this->assertStringNotContainsString('name="source_files[]"', $repository);
        $this->assertStringNotContainsString('name="subtopics_text"', $repository);
        $this->assertStringContainsString('name="source_files[]"', $import);
        $this->assertStringContainsString('id="uploadProgress"', $import);
        $this->assertStringContainsString("route('super.curriculum-sources.store')", $import);
        $this->assertStringContainsString('value="lesson_note"', $import);
        $this->assertStringContainsString('Class → Subject → Term', $import);
        $this->assertStringNotContainsString('name="subtopics_text"', $import);
        $this->assertStringContainsString('name="subtopics_text"', $topics);
        $this->assertStringContainsString("route('super.curriculum-sources.topics.store')", $topics);
        $this->assertStringNotContainsString('name="source_files[]"', $topics);
    }
}
