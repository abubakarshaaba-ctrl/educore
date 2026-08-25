<?php

namespace Tests\Feature;

use App\Contracts\LessonAiProvider;
use App\Models\User;
use App\Services\LessonPlanning\StructuredLessonPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonPlannerFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_failure_returns_an_editable_structured_lesson_draft(): void
    {
        $this->actingAs(User::factory()->create());
        $this->app->instance(LessonAiProvider::class, new class implements LessonAiProvider {
            public function name(): string { return 'unavailable'; }
            public function model(): string { return 'offline'; }
            public function generateStructured(string $systemPrompt, string $userPrompt, array $schema, int $maxTokens): array
            {
                throw new \RuntimeException('Provider unavailable');
            }
        });

        $plan = app(StructuredLessonPlanService::class)->generate([
            'subject'=>'Biology','class_level'=>'SS 2','topic'=>'Homeostasis',
            'subtopic'=>'Meaning of homeostasis; Temperature regulation','week'=>'2',
            'lesson'=>'1','duration_minutes'=>40,'sex'=>'Mixed',
            'repository_context'=>[['source'=>'Prepared Biology Notes','requirement'=>'Homeostasis maintains a stable internal environment.']],
        ]);

        $this->assertSame('Homeostasis', $plan['topic']);
        $this->assertCount(3, $plan['behavioural_objectives']);
        $this->assertSame([1], $plan['presentation'][0]['objective_numbers']);
        $this->assertSame([2, 3], $plan['presentation'][1]['objective_numbers']);
        $this->assertSame(['Prepared Biology Notes'], $plan['references']);
        $this->assertDatabaseHas('ai_usage_logs', ['provider'=>'built_in','status'=>'completed']);
    }
}
