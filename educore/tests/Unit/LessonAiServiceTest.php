<?php

namespace Tests\Unit;

use App\Services\LessonAiService;
use Tests\TestCase;

class LessonAiServiceTest extends TestCase
{
    public function test_nerdc_generation_uses_a_complete_local_fallback_when_no_provider_is_available(): void
    {
        config([
            'services.ai_provider' => 'gemini',
            'services.gemini.key' => null,
            'services.groq.key' => null,
            'services.openrouter.key' => null,
            'services.anthropic.key' => null,
            'services.ollama.enabled' => false,
        ]);

        $plan = app(LessonAiService::class)->generateNerdcPlan([
            'subject' => 'Basic Science',
            'class_level' => 'JSS 1',
            'topic' => 'Living Things',
            'subtopic' => 'Characteristics of living things',
            'duration_minutes' => 40,
        ]);

        $this->assertSame([
            'previous_knowledge',
            'entry_behaviour',
            'behavioural_objectives',
            'instructional_materials',
            'reference_materials',
            'set_induction',
            'presentation',
            'class_activity',
            'evaluation',
            'assignment',
            'conclusion',
        ], array_keys($plan));
        $this->assertStringContainsString('Living Things', $plan['presentation']);
        $this->assertStringContainsString('By the end of this lesson', $plan['behavioural_objectives']);
    }

    public function test_british_generation_uses_a_complete_local_fallback_when_no_provider_is_available(): void
    {
        config([
            'services.ai_provider' => 'gemini',
            'services.gemini.key' => null,
            'services.groq.key' => null,
            'services.openrouter.key' => null,
            'services.anthropic.key' => null,
            'services.ollama.enabled' => false,
        ]);

        $plan = app(LessonAiService::class)->generateBritishPlan([
            'topic' => 'Fractions',
            'subtopic' => 'Equivalent fractions',
        ]);

        $this->assertArrayHasKey('success_criteria', $plan);
        $this->assertArrayHasKey('assessment_for_learning', $plan);
        $this->assertStringContainsString('Fractions', $plan['learning_objectives']);
    }
}
