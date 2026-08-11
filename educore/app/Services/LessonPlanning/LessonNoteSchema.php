<?php

namespace App\Services\LessonPlanning;

use Illuminate\Support\Facades\Validator;

class LessonNoteSchema
{
    public function validate(array $data): array
    {
        foreach ($data['sections'] ?? [] as $sectionIndex => $section) foreach ($section['content_blocks'] ?? [] as $blockIndex => $block) {
            $type=mb_strtolower((string)($block['type']??''));
            $aliases=['text'=>'paragraph','list'=>'bullets','bullet_list'=>'bullets','example'=>'worked_example','worked-example'=>'worked_example','image'=>'diagram'];
            $type=$aliases[$type]??$type;
            if(!in_array($type,['paragraph','bullets','table','worked_example','diagram'],true)) {
                $type=isset($block['rows'])?'table':(isset($block['items'])?'bullets':(isset($block['labels'])?'diagram':'paragraph'));
            }
            $data['sections'][$sectionIndex]['content_blocks'][$blockIndex]['type']=$type;
        }
        return Validator::make($data, [
            'title' => 'required|string', 'overview' => 'required|string', 'sections' => 'required|array|min:1',
            'sections.*.heading' => 'required|string', 'sections.*.subheading' => 'nullable|string',
            'sections.*.content_blocks' => 'required|array|min:1', 'sections.*.content_blocks.*.type' => 'required|in:paragraph,bullets,table,worked_example,diagram',
            'key_examination_points' => 'present|array', 'review_questions' => 'required|array',
            'review_questions.objective' => 'present|array', 'review_questions.structured' => 'present|array',
            'review_questions.application' => 'present|array', 'summary' => 'required|string', 'source_trace' => 'present|array',
        ])->validate();
    }

    public function jsonSchema(): array { return ['type'=>'object','required'=>['title','overview','sections','key_examination_points','review_questions','summary','source_trace']]; }
}
