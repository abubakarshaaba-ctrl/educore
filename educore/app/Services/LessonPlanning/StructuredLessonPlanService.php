<?php

namespace App\Services\LessonPlanning;

use App\Contracts\LessonAiProvider;
use App\Models\AiUsageLog;
use Illuminate\Validation\ValidationException;

class StructuredLessonPlanService
{
    public function __construct(private LessonAiProvider $provider, private LessonPlanSchema $schema) {}

    public function generate(array $input): array
    {
        $system = <<<'PROMPT'
You generate substantive, classroom-ready lesson plans inside EduCore. Return only valid JSON and never rename, omit or add top-level fields.

CONTENT STANDARD:
- Write specific instructional content for the supplied subject, class, topic and every supplied subtopic. Never use vague filler such as "discuss the topic" or "explain key concepts".
- entry_behaviour states an observable prerequisite ability students presently demonstrate. previous_background_knowledge states related concepts they were taught previously. Never merge them.
- Write 3-6 measurable behavioural objectives using observable verbs. Collectively cover every subtopic.
- Select concrete, topic-appropriate instructional resources.
- The introduction must show the teacher revising relevant previous learning through questions, students responding, and the teacher linking those responses to the new lesson.
- Create at least one presentation step for EACH subtopic, in the same order as sub_topics. A step title must name its subtopic.
- Every presentation step must contain 3-5 complete, academically meaningful teacher actions. Use the institutional teaching style naturally: "Teacher guides the students to...", "Teacher aids the students to...", "Teacher helps the students to...", "Teacher demonstrates...", or equivalent. State the actual definitions, classifications, processes, examples, comparisons or worked procedures to be taught; do not merely promise to teach them.
- Evaluation questions must directly assess the stated objectives and cover all substantive subtopics. Assignment must extend the same lesson scope.
- Use Nigerian English, age-appropriate examples and inclusive learner participation. Build each step as teacher activity followed by the expected learner response or practice, even though both are stored in the activities list.
- Sequence the lesson from prerequisite recall to explanation/modelling, guided practice, independent practice and formative assessment. Do not claim that learners already know the new lesson content.
- Make the lesson feasible within the supplied duration. Do not invent experiments, equipment or local circumstances that were not supplied or are unsafe.
- References may contain only sources supplied in the request or verified curriculum context. Return [] when no verified reference is supplied; never invent titles, authors or page numbers.
PROMPT;
        $prompt = 'Create a lesson plan using this specification: '.json_encode($input, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            .' Required keys: class, subject, week, lesson, topic, sub_topics[], time, duration, average_age, sex, entry_behaviour, previous_background_knowledge, behavioural_objectives[], instructional_resources[], introduction, presentation[{step,title,activities[]}], evaluation[], assignment, references[]. The presentation array must contain at least one full step per subtopic and preserve subtopic order.';
        $started=hrtime(true);$result=null;$failure=null;
        try {
            $result=$this->provider->generateStructured($system,$prompt,$this->schema->jsonSchema(),3200);
            try {
                return $this->schema->validate($result['data']);
            } catch (ValidationException $validation) {
                $repairPrompt = $prompt."\nThe previous draft was structurally or instructionally incomplete. Correct ONLY the identified weaknesses and return the complete corrected JSON. Validation issues: "
                    .json_encode($validation->errors(), JSON_UNESCAPED_UNICODE)
                    ."\nPrevious draft: ".json_encode($result['data'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                $result=$this->provider->generateStructured($system,$repairPrompt,$this->schema->jsonSchema(),3200);
                return $this->schema->validate($result['data']);
            }
        }
        catch(\Throwable $e){$failure=$e;throw $e;}
        finally { AiUsageLog::create(['tenant_id'=>auth()->user()?->tenant_id,'user_id'=>auth()->id(),'feature'=>'lesson_planner','provider'=>$this->provider->name(),'model'=>$this->provider->model(),'request_type'=>'generate_lesson_plan','input_tokens'=>$result['input_tokens']??null,'output_tokens'=>$result['output_tokens']??null,'total_tokens'=>isset($result['input_tokens'],$result['output_tokens'])?$result['input_tokens']+$result['output_tokens']:null,'status'=>$failure?'failed':'completed','latency_ms'=>(int)((hrtime(true)-$started)/1_000_000),'error_code'=>$failure?class_basename($failure):null]); }
    }

    public function legacyFields(array $plan): array
    {
        return ['entry_behaviour'=>$plan['entry_behaviour'],'previous_knowledge'=>$plan['previous_background_knowledge'],
            'behavioural_objectives'=>"At the end of the lesson, students should be able to:\n".collect($plan['behavioural_objectives'])->map(fn($v,$i)=>($i+1).'. '.$v)->implode("\n"),
            'instructional_materials'=>collect($plan['instructional_resources'])->map(fn($v)=>'- '.$v)->implode("\n"),
            'reference_materials'=>collect($plan['references'])->map(fn($v,$i)=>($i+1).'. '.$v)->implode("\n"),'set_induction'=>$plan['introduction'],
            'presentation'=>collect($plan['presentation'])->map(fn($step)=>'STEP '.$step['step'].': '.$step['title']."\n".implode("\n",$step['activities']))->implode("\n\n"),
            'evaluation'=>collect($plan['evaluation'])->map(fn($v,$i)=>($i+1).'. '.$v)->implode("\n"),'assignment'=>$plan['assignment'],'structured_plan'=>$plan];
    }
}
