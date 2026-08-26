<?php

namespace App\Services\LessonPlanning;

use Illuminate\Support\Facades\Validator;

class LessonNoteSchema
{
    public function validate(array $data): array
    {
        foreach (['week', 'lesson', 'topic'] as $field) {
            if (array_key_exists($field, $data) && is_scalar($data[$field])) {
                $data[$field] = trim((string) $data[$field]);
            }
        }

        if (! isset($data['sub_topics']) && isset($data['subtopics'])) {
            $data['sub_topics'] = $data['subtopics'];
        }

        foreach ($data['sections'] ?? [] as $sectionIndex => $section) {
            foreach ($section['content_blocks'] ?? [] as $blockIndex => $block) {
                $type = mb_strtolower((string) ($block['type'] ?? ''));
                $aliases = ['text'=>'paragraph', 'list'=>'bullets', 'bullet_list'=>'bullets', 'example'=>'worked_example', 'worked-example'=>'worked_example', 'image'=>'diagram'];
                $type = $aliases[$type] ?? $type;
                if (! in_array($type, ['paragraph', 'bullets', 'table', 'worked_example', 'diagram'], true)) {
                    $type = isset($block['rows']) ? 'table' : (isset($block['items']) ? 'bullets' : (isset($block['labels']) ? 'diagram' : 'paragraph'));
                }
                $data['sections'][$sectionIndex]['content_blocks'][$blockIndex]['type'] = $type;
                if ($type === 'paragraph' && empty($block['content'])) {
                    $data['sections'][$sectionIndex]['content_blocks'][$blockIndex]['content'] = $block['text'] ?? $block['description'] ?? '';
                }
            }
        }

        $validator = Validator::make($data, [
            'week' => 'nullable|string|max:30',
            'lesson' => 'required|string|max:40',
            'topic' => 'required|string|max:255',
            'sub_topics' => 'required|array|min:1',
            'sub_topics.*' => 'required|string|max:255',
            'sections' => 'required|array|min:1',
            'sections.*.heading' => 'required|string',
            'sections.*.subheading' => 'nullable|string',
            'sections.*.content_blocks' => 'required|array|min:1',
            'sections.*.content_blocks.*.type' => 'required|in:paragraph,bullets,table,worked_example,diagram',
            'evaluation' => 'required|array|min:3|max:12',
            'evaluation.*' => 'required|string|min:8',
            'assignment' => 'required|string|min:8',
            'reading_assignment' => 'required|string|min:8',
            'source_trace' => 'present|array',
        ]);

        $validator->after(function ($validator) use ($data) {
            foreach ($data['sections'] ?? [] as $sectionIndex => $section) {
                $words = 0;
                foreach ($section['content_blocks'] ?? [] as $blockIndex => $block) {
                    $text = collect(['content', 'text', 'description', 'problem', 'solution'])
                        ->map(fn ($key) => (string) ($block[$key] ?? ''))->implode(' ')
                        .' '.implode(' ', array_map('strval', $block['items'] ?? []))
                        .' '.json_encode($block['rows'] ?? [], JSON_UNESCAPED_UNICODE);
                    $words += str_word_count(strip_tags($text));

                    if (($block['type'] ?? null) === 'diagram' && count($block['labels'] ?? []) < 2) {
                        $validator->errors()->add("sections.{$sectionIndex}.content_blocks.{$blockIndex}.labels", 'A study diagram must contain at least two meaningful labels.');
                    }
                }
                if ($words < 45) {
                    $validator->errors()->add("sections.{$sectionIndex}.content_blocks", 'Each student-note section must contain substantive explanations, not headings or labels only.');
                }
            }

            $total = str_word_count(strip_tags(json_encode($data['sections'] ?? [], JSON_UNESCAPED_UNICODE)));
            if ($total < 350) {
                $validator->errors()->add('sections', 'The student note is too shallow. Generate complete definitions, explanations, examples, processes and learner-ready detail.');
            }
        });

        return $validator->validate();
    }

    public function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['week', 'lesson', 'topic', 'sub_topics', 'sections', 'evaluation', 'assignment', 'reading_assignment', 'source_trace'],
        ];
    }
}
