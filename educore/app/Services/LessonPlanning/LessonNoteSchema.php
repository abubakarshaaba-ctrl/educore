<?php

namespace App\Services\LessonPlanning;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
            if($type==='paragraph' && empty($block['content'])) {
                $data['sections'][$sectionIndex]['content_blocks'][$blockIndex]['content']=$block['text']??$block['description']??'';
            }
        }
        $validator=Validator::make($data, [
            'title' => 'required|string', 'overview' => 'required|string', 'sections' => 'required|array|min:1',
            'sections.*.heading' => 'required|string', 'sections.*.subheading' => 'nullable|string',
            'sections.*.content_blocks' => 'required|array|min:1', 'sections.*.content_blocks.*.type' => 'required|in:paragraph,bullets,table,worked_example,diagram',
            'key_examination_points' => 'present|array', 'review_questions' => 'required|array',
            'review_questions.objective' => 'present|array', 'review_questions.structured' => 'present|array',
            'review_questions.application' => 'present|array', 'summary' => 'required|string', 'source_trace' => 'present|array',
        ]);
        $validator->after(function($validator) use($data){
            foreach($data['sections']??[] as $sectionIndex=>$section){
                $words=0;
                foreach($section['content_blocks']??[] as $block){
                    $text=collect(['content','text','description','problem','solution'])->map(fn($key)=>(string)($block[$key]??''))->implode(' ')
                        .' '.implode(' ',array_map('strval',$block['items']??[]))
                        .' '.json_encode($block['rows']??[],JSON_UNESCAPED_UNICODE);
                    $words+=str_word_count(strip_tags($text));
                }
                if($words<45) $validator->errors()->add("sections.{$sectionIndex}.content_blocks",'Each lesson-note section must contain substantive explanatory content, not headings or labels only.');
            }
            $total=str_word_count(strip_tags(json_encode($data['sections']??[],JSON_UNESCAPED_UNICODE)));
            if($total<180) $validator->errors()->add('sections','The lesson note is too shallow. Generate complete definitions, explanations, examples and learner-ready detail.');
        });
        return $validator->validate();
    }

    public function jsonSchema(): array { return ['type'=>'object','required'=>['title','overview','sections','key_examination_points','review_questions','summary','source_trace']]; }
}
