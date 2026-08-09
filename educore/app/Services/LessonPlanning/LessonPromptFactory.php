<?php

namespace App\Services\LessonPlanning;

use App\Models\LessonPlan;

class LessonPromptFactory
{
    public function noteSystem(): string
    {
        return <<<'PROMPT'
You are an instructional content generator inside EduCore. Return only valid JSON matching the supplied schema. The approved lesson plan is authoritative for required lesson scope. The supplied curriculum evidence is authoritative for NERDC, WAEC, NECO, JAMB and textbook alignment. Never invent curriculum requirements, citations, page numbers or examination-body claims. Clearly separate evidence-grounded requirements from supplementary explanation. Cover every subtopic and measurable objective. Generate academically accurate, class-appropriate teaching content; use applicable explanations, examples, comparisons, formulae, worked examples and diagram placeholders. Questions must be original examination-style questions, never represented as past questions. Do not return HTML, markdown or binary images.
PROMPT;
    }

    public function noteUser(LessonPlan $plan, array $context, string $depth, ?array $onlyItems = null): string
    {
        $specification = [
            'class' => $plan->classLevel->name, 'subject' => $plan->subject->name, 'topic' => $plan->topic,
            'subtopics' => preg_split('/[,;\n]+/', (string) $plan->subtopic),
            'behavioural_objectives' => preg_split('/\r?\n/', (string) ($plan->behavioural_objectives ?: $plan->learning_objectives)),
            'entry_behaviour' => $plan->entry_behaviour, 'previous_background_knowledge' => $plan->previous_knowledge,
            'instructional_resources' => $plan->instructional_materials, 'duration_minutes' => $plan->duration_minutes,
            'presentation' => $plan->presentation, 'depth' => $depth,
        ];
        return "Generate a structured lesson note.\nAPPROVED_PLAN=".json_encode($specification, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            ."\nCURRICULUM_EVIDENCE=".json_encode($context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            .($onlyItems ? "\nGENERATE_ONLY_MISSING_ITEMS=".json_encode($onlyItems, JSON_UNESCAPED_UNICODE) : '')
            .'\nRequired JSON keys: title, overview, sections[{heading,subheading,content_blocks[{type,content|items|headers|rows|title|problem|solution|labels,placement_after_section}]}], key_examination_points[], review_questions{objective[],structured[],application[]}, summary, source_trace[]. Source trace may contain only supplied fragment IDs and locators.';
    }
}
