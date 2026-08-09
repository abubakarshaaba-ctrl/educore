<?php

namespace App\Services\LessonPlanning;

use App\Contracts\LessonAiProvider;
use App\Models\AiUsageLog;

class StructuredLessonPlanService
{
    public function __construct(private LessonAiProvider $provider, private LessonPlanSchema $schema) {}

    public function generate(array $input): array
    {
        $system = 'You generate editable lesson-plan content inside EduCore. Return only valid JSON. Never rename, omit or add top-level fields. Keep entry_behaviour (observable readiness) separate from previous_background_knowledge (previously taught concepts). Use measurable objectives and teacher-guided presentation activities. Do not invent curriculum or textbook citations.';
        $prompt = 'Create a lesson plan using this specification: '.json_encode($input, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            .' Required keys: class, subject, week, lesson, topic, sub_topics[], time, duration, average_age, sex, entry_behaviour, previous_background_knowledge, behavioural_objectives[], instructional_resources[], introduction, presentation[{step,title,activities[]}], evaluation[], assignment, references[].';
        $started=hrtime(true);$result=null;$failure=null;
        try { $result=$this->provider->generateStructured($system,$prompt,$this->schema->jsonSchema(),2600); return $this->schema->validate($result['data']); }
        catch(\Throwable $e){$failure=$e;throw $e;}
        finally { AiUsageLog::create(['tenant_id'=>auth()->user()?->tenant_id,'user_id'=>auth()->id(),'feature'=>'lesson_planner','provider'=>$this->provider->name(),'model'=>$this->provider->model(),'request_type'=>'generate_lesson_plan','input_tokens'=>$result['input_tokens']??null,'output_tokens'=>$result['output_tokens']??null,'total_tokens'=>isset($result['input_tokens'],$result['output_tokens'])?$result['input_tokens']+$result['output_tokens']:null,'status'=>$failure?'failed':'completed','latency_ms'=>(int)((hrtime(true)-$started)/1_000_000),'error_code'=>$failure?class_basename($failure):null]); }
    }

    public function legacyFields(array $plan): array
    {
        return ['entry_behaviour'=>$plan['entry_behaviour'],'previous_knowledge'=>$plan['previous_background_knowledge'],
            'behavioural_objectives'=>"At the end of the lesson, students should be able to:\n".collect($plan['behavioural_objectives'])->map(fn($v,$i)=>($i+1).'. '.$v)->implode("\n"),
            'instructional_materials'=>collect($plan['instructional_resources'])->map(fn($v)=>'- '.$v)->implode("\n"),
            'reference_materials'=>collect($plan['references'])->map(fn($v,$i)=>($i+1).'. '.$v)->implode("\n"),'set_induction'=>$plan['introduction'],
            'presentation'=>collect($plan['presentation'])->map(fn($step)=>'STEP '.$this->roman((int)$step['step']).': '.$step['title']."\n".implode("\n",$step['activities']))->implode("\n\n"),
            'evaluation'=>collect($plan['evaluation'])->map(fn($v,$i)=>($i+1).'. '.$v)->implode("\n"),'assignment'=>$plan['assignment'],'structured_plan'=>$plan];
    }

    private function roman(int $number): string
    {
        $map = [10=>'X',9=>'IX',5=>'V',4=>'IV',1=>'I']; $result = '';
        foreach ($map as $value=>$numeral) while ($number >= $value) { $result .= $numeral; $number -= $value; }
        return $result ?: 'I';
    }
}
