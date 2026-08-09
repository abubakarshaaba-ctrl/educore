<?php

namespace App\Services\LessonPlanning;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LessonPlanSchema
{
    public function validate(array $data): array
    {
        return Validator::make($data, [
            'class' => 'required|string|max:120', 'subject' => 'required|string|max:120', 'week' => 'nullable',
            'lesson' => 'required|string|max:120', 'topic' => 'required|string|max:255', 'sub_topics' => 'required|array|min:1',
            'sub_topics.*' => 'required|string', 'time' => 'nullable|string|max:50', 'duration' => 'required|string|max:50',
            'average_age' => 'nullable|string|max:50', 'sex' => 'required|string|max:50', 'entry_behaviour' => 'required|string',
            'previous_background_knowledge' => 'required|string', 'behavioural_objectives' => 'required|array|min:1',
            'behavioural_objectives.*' => 'required|string', 'instructional_resources' => 'required|array|min:1',
            'instructional_resources.*' => 'required|string', 'introduction' => 'required|string',
            'presentation' => 'required|array|min:1', 'presentation.*.step' => 'required|integer|min:1',
            'presentation.*.title' => 'required|string', 'presentation.*.activities' => 'required|array|min:1',
            'presentation.*.activities.*' => 'required|string', 'evaluation' => 'required|array|min:1',
            'evaluation.*' => 'required|string', 'assignment' => 'required|string', 'references' => 'present|array',
        ])->validate();
    }

    public function jsonSchema(): array
    {
        return ['type' => 'object', 'required' => ['class','subject','lesson','topic','sub_topics','duration','sex','entry_behaviour','previous_background_knowledge','behavioural_objectives','instructional_resources','introduction','presentation','evaluation','assignment','references']];
    }
}
