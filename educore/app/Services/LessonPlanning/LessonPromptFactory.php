<?php

namespace App\Services\LessonPlanning;

use App\Models\LessonPlan;

class LessonPromptFactory
{
    public function noteSystem(): string
    {
        return <<<'PROMPT'
You are an expert Nigerian secondary-school lesson-note writer inside EduCore. Return only valid JSON matching the supplied schema. Follow the approved WEEK 1 student-note specimen exactly for content sequence and depth. The approved lesson plan is authoritative for lesson scope. Supplied repository evidence is authoritative for curriculum and source alignment. Never invent curriculum requirements, citations, page numbers or examination-body claims. If repository evidence is empty, still write a complete academically accurate note from the approved plan and general subject knowledge, but make no NERDC alignment claim.

CONTENT QUALITY:
- Begin with week, lesson, topic and the complete ordered sub-topic list. Then write one or more detailed sections for every sub-topic in the same order as the approved plan.
- A standard note should normally contain 900-1,400 useful words; concise 500-800; detailed 1,400-2,200.
- Every section needs learner-ready definitions, full explanations, relevant familiar or Nigerian examples, important distinctions, processes, classifications and applications. Never return headings, labels, fragments or outline-only content.
- Use paragraph and bullet blocks for real content. Use a table where comparison improves understanding. Add a diagram block wherever a labelled figure, cycle, structure, process or sequence is educationally important; provide a precise title, caption, short description and 2-8 ordered labels.
- End with evaluation questions that test the lesson scope, one assignment and one reading assignment. A reading assignment may cite only a verified supplied source; otherwise state a topic-based reading task without inventing an author, title or page number.
- Do not add textbook-style extras such as an overview, key examination points, model answers, summary, examination-body question categories or source claims outside the specimen structure.
- Do not return HTML, markdown or binary images.
PROMPT;
    }

    public function noteUser(LessonPlan $plan, array $context, string $depth, ?array $onlyItems = null): string
    {
        $subtopics = collect(preg_split('/[,;\n]+/', (string) $plan->subtopic, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($value) => trim($value))->filter()->values();
        if ($subtopics->isEmpty()) $subtopics = collect([$plan->topic]);

        $specification = [
            'class' => $plan->classLevel->name, 'subject' => $plan->subject->name,
            'term' => $plan->term?->name, 'week' => (string) ($plan->week_number ?? ''),
            'lesson' => (string) ($plan->lesson_number ?: '1'), 'topic' => $plan->topic,
            'subtopics' => $subtopics->all(),
            'behavioural_objectives' => preg_split('/\r?\n/', (string) ($plan->behavioural_objectives ?: $plan->learning_objectives)),
            'previous_background_knowledge' => $plan->previous_knowledge,
            'instructional_resources' => $plan->instructional_materials, 'duration_minutes' => $plan->duration_minutes,
            'presentation' => $plan->presentation, 'depth' => $depth,
        ];
        $curriculum=collect($context)->where('source_type','!=','web_reference')->values()->all();
        $web=collect($context)->where('source_type','web_reference')->values()->all();
        return "Generate a structured lesson note.\nAPPROVED_PLAN=".json_encode($specification, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            ."\nAUTHORITATIVE_CURRICULUM_EVIDENCE=".json_encode($curriculum, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            ."\nSUPPLEMENTARY_WEB_EVIDENCE=".json_encode($web, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            .($onlyItems ? "\nGENERATE_ONLY_MISSING_ITEMS=".json_encode($onlyItems, JSON_UNESCAPED_UNICODE) : '')
            .'\nReturn exactly these top-level JSON keys: week, lesson, topic, sub_topics[], sections[{heading,subheading,content_blocks[{type,content|items|headers|rows|title|caption|description|problem|solution|labels}]}], evaluation[], assignment, reading_assignment, source_trace[]. Source trace may contain only supplied fragment_id or evidence_id values. Web evidence is supplementary and must never be described as NERDC alignment.';
    }
}
