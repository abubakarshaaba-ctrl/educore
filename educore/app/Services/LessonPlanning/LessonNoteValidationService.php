<?php

namespace App\Services\LessonPlanning;

use App\Models\LessonPlan;

class LessonNoteValidationService
{
    public function validate(LessonPlan $plan, array $note, array $context): array
    {
        $haystack = mb_strtolower(json_encode($note, JSON_UNESCAPED_UNICODE));
        $subtopics = collect(preg_split('/[,;\n]+/', (string) $plan->subtopic))->map(fn ($v) => trim($v))->filter();
        $objectives = collect(preg_split('/\r?\n/', (string) ($plan->behavioural_objectives ?: $plan->learning_objectives)))->map(fn ($v) => trim(preg_replace('/^\s*\d+[.)]\s*/', '', $v)))->filter();
        $missingSubtopics = $subtopics->filter(fn ($item) => ! $this->covered($haystack, $item))->values();
        $missingObjectives = $objectives->filter(fn ($item) => ! $this->covered($haystack, $item))->values();
        $missing = $missingSubtopics->map(fn ($v) => "Subtopic: {$v}")->merge($missingObjectives->map(fn ($v) => "Objective: {$v}"))->values()->all();
        $authorities = collect($context)->groupBy('authority')->map(fn ($items) => $items->isNotEmpty() ? 'SUBSTANTIAL' : 'INSUFFICIENT')->all();
        foreach (['NERDC','WAEC','NECO','JAMB'] as $authority) $authorities[$authority] ??= 'NOT_APPLICABLE';

        return ['status' => $missing ? 'revise' : 'pass', 'plan_coverage' => $missing ? 'PARTIAL' : 'FULL',
            'authority_alignment' => $authorities, 'missing_plan_items' => $missing, 'missing_curriculum_items' => [],
            'factual_concerns' => $context ? [] : ['No approved curriculum evidence matched this lesson. Alignment claims are disabled pending teacher review.'],
            'suggested_additions' => $missing];
    }

    private function covered(string $haystack, string $needle): bool
    {
        $keywords = collect(preg_split('/\W+/u', mb_strtolower($needle)))->filter(fn ($w) => mb_strlen($w) >= 5)->take(4);
        return $keywords->isNotEmpty() && $keywords->contains(fn ($word) => str_contains($haystack, $word));
    }
}
