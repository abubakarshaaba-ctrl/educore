<?php

namespace App\Services\LessonPlanning;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LessonPlanSchema
{
    public function validate(array $data): array
    {
        // JSON providers commonly encode human-readable scalar fields as
        // numbers (for example 40 instead of "40 minutes"). These values are
        // semantically valid, so canonicalise them before strict validation.
        foreach (['class', 'subject', 'week', 'lesson', 'topic', 'time', 'duration', 'average_age', 'sex'] as $field) {
            if (array_key_exists($field, $data) && is_scalar($data[$field])) {
                $data[$field] = trim((string) $data[$field]);
            }
        }

        // An explicit null reference list means the provider found no safe
        // references. Preserve that meaning as an empty list; never invent one.
        if (array_key_exists('references', $data) && $data['references'] === null) {
            $data['references'] = [];
        }

        $validator = Validator::make($data, [
            'class' => 'required|string|max:120', 'subject' => 'required|string|max:120', 'week' => 'nullable',
            'lesson' => 'required|string|max:120', 'topic' => 'required|string|max:255', 'sub_topics' => 'required|array|min:1',
            'sub_topics.*' => 'required|string', 'time' => 'nullable|string|max:50', 'duration' => 'required|string|max:50',
            'average_age' => 'nullable|string|max:50', 'sex' => 'required|string|max:50',
            'previous_background_knowledge' => 'required|string|min:20', 'behavioural_objectives' => 'required|array|min:3|max:8',
            'behavioural_objectives.*' => 'required|string|min:12', 'instructional_resources' => 'required|array|min:2',
            'instructional_resources.*' => 'required|string', 'introduction' => 'required|string|min:40',
            'presentation' => 'required|array|min:1', 'presentation.*.step' => 'required|integer|min:1',
            'presentation.*.objective_numbers' => 'required|array|min:1', 'presentation.*.objective_numbers.*' => 'required|integer|min:1',
            'presentation.*.title' => 'required|string|min:3', 'presentation.*.teacher_activities' => 'required|array|min:3|max:6',
            'presentation.*.teacher_activities.*' => 'required|string|min:25', 'evaluation' => 'required|array|min:3|max:10',
            'evaluation.*' => 'required|string|min:8', 'assignment' => 'required|string|min:10', 'references' => 'present|array',
        ]);

        $validator->after(function ($validator) use ($data) {
            $subtopics = count($data['sub_topics'] ?? []);
            $objectiveCount = count($data['behavioural_objectives'] ?? []);
            if ($subtopics > 0 && $objectiveCount < $subtopics) {
                $validator->errors()->add('behavioural_objectives', 'The behavioural objectives do not cover every lesson subtopic.');
            }
            $mapped=[];
            foreach($data['presentation']??[] as $step) foreach($step['objective_numbers']??[] as $number) $mapped[]=(int)$number;
            sort($mapped); $expected=$objectiveCount?range(1,$objectiveCount):[];
            if($mapped!==$expected) $validator->errors()->add('presentation','Presentation steps must map every behavioural objective exactly once and in objective order.');

            $measurableVerbs = 'define|identify|list|state|describe|explain|distinguish|differentiate|classify|calculate|solve|demonstrate|construct|draw|label|analyse|compare|evaluate|apply|outline|mention|name';
            foreach ($data['behavioural_objectives'] ?? [] as $index => $objective) {
                if (! preg_match('/\b('.$measurableVerbs.')\b/i', (string) $objective)) {
                    $validator->errors()->add("behavioural_objectives.{$index}", 'Use an observable, measurable behavioural verb.');
                }
            }

            $generic = '/\b(discuss the topic|explain key concepts|teach the students|provide examples|give examples|cover the topic)\b/i';
            foreach ($data['presentation'] ?? [] as $index => $step) {
                $title = mb_strtolower((string) ($step['title'] ?? ''));
                $expected = mb_strtolower((string) (($data['sub_topics'] ?? [])[$index] ?? ''));
                $keywords = preg_split('/\W+/u', $expected, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $matched = collect($keywords)->contains(fn ($word) => mb_strlen($word) > 3 && str_contains($title, $word));
                if ($expected !== '' && ! $matched) {
                    $validator->errors()->add("presentation.{$index}.title", 'The step title must clearly identify its assigned subtopic.');
                }
                foreach ($step['teacher_activities'] ?? [] as $activityIndex => $activity) {
                    if (preg_match($generic, (string) $activity)) {
                        $validator->errors()->add("presentation.{$index}.activities.{$activityIndex}", 'Replace generic teaching instructions with the actual concept, process, example or worked procedure.');
                    }
                }
            }
        });

        return $validator->validate();
    }

    public function jsonSchema(): array
    {
        return ['type'=>'object','required'=>['class','subject','lesson','topic','sub_topics','duration','sex','previous_background_knowledge','behavioural_objectives','instructional_resources','introduction','presentation','evaluation','assignment','references']];
    }
}
