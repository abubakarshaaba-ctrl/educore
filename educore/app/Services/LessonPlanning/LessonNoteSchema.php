<?php

namespace App\Services\LessonPlanning;

use Illuminate\Support\Facades\Validator;

class LessonNoteSchema
{
    public function validate(array $data): array
    {
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
