<?php

namespace App\Services\LessonPlanning;

use App\Models\LessonPlan;

class LessonPromptFactory
{
    public function noteSystem(): string
    {
        return <<<'PROMPT'
You are an expert Nigerian secondary-school lesson-note writer inside EduCore. Return only valid JSON matching the supplied schema. The approved lesson plan is authoritative for lesson scope. Supplied curriculum evidence is authoritative for NERDC, WAEC, NECO, JAMB and textbook alignment. Never invent curriculum requirements, citations, page numbers or examination-body claims. If curriculum evidence is empty, still write a complete academically accurate note from the approved plan and general subject knowledge, but make no NERDC alignment claim.

CONTENT QUALITY:
- Cover every subtopic, behavioural objective and presentation step in the same order.
- A standard note should normally contain 700-1,100 useful words; concise 450-700; detailed 1,200-1,800.
- Every section needs learner-ready definitions and explanation, specific Nigerian or familiar examples where appropriate, important distinctions/processes, and at least one check-for-understanding point. Never return headings, labels, fragments or outline-only content.
- Use paragraph and bullet blocks for real content. Tables, diagrams and worked examples supplement explanations; they never replace them.
- Include accurate terminology, examination points, a coherent summary, and original objective, structured and application questions. Never represent questions as past questions.
- Do not return HTML, markdown or binary images.
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
        $curriculum=collect($context)->where('source_type','!=','web_reference')->values()->all();
        $web=collect($context)->where('source_type','web_reference')->values()->all();
        return "Generate a structured lesson note.\nAPPROVED_PLAN=".json_encode($specification, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            ."\nAUTHORITATIVE_CURRICULUM_EVIDENCE=".json_encode($curriculum, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            ."\nSUPPLEMENTARY_WEB_EVIDENCE=".json_encode($web, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            .($onlyItems ? "\nGENERATE_ONLY_MISSING_ITEMS=".json_encode($onlyItems, JSON_UNESCAPED_UNICODE) : '')
            .'\nRequired JSON keys: title, overview, sections[{heading,subheading,content_blocks[{type,content|items|headers|rows|title|problem|solution|labels,placement_after_section}]}], key_examination_points[], review_questions{objective[],structured[],application[]}, summary, source_trace[]. Source trace may contain only supplied fragment_id or evidence_id values. Web evidence is supplementary and must never be described as NERDC alignment.';
    }
}
