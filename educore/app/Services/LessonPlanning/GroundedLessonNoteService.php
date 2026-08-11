<?php

namespace App\Services\LessonPlanning;

use App\Contracts\LessonAiProvider;
use App\Models\AiUsageLog;
use App\Models\LessonNoteRevision;
use App\Models\LessonNoteValidation;
use App\Models\LessonPlan;
use App\Services\Curriculum\CurriculumRetrievalService;
use App\Services\Curriculum\WebLessonResearchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GroundedLessonNoteService
{
    public function __construct(private LessonAiProvider $provider, private CurriculumRetrievalService $retrieval, private WebLessonResearchService $webResearch,
        private LessonPromptFactory $prompts, private LessonNoteSchema $schema, private LessonNoteValidationService $validator,
        private StructuredNoteRenderer $renderer) {}

    public function generate(LessonPlan $plan, int $userId, string $depth = 'standard', ?array $onlyItems = null): LessonNoteRevision
    {
        if (! $plan->isPublished() && ! $plan->approved_at) throw new \DomainException('Approve the lesson plan before generating its lesson note.');
        $plan->loadMissing(['subject', 'classLevel']);
        $fragments = $this->retrieval->forLessonPlan($plan);
        $curriculumContext = $this->retrieval->compactContext($fragments);
        $webContext = $this->webResearch->forLessonPlan($plan);
        $context = array_merge($curriculumContext,$webContext);
        $started = hrtime(true); $usage = null; $failure = null;
        try {
            $usage = $this->provider->generateStructured($this->prompts->noteSystem(), $this->prompts->noteUser($plan, $context, $depth, $onlyItems), $this->schema->jsonSchema(), $depth === 'detailed' ? 6000 : ($depth === 'concise' ? 2600 : 4200));
            try {
                $content = $this->schema->validate($usage['data']);
            } catch (ValidationException $validation) {
                $repairPrompt=$this->prompts->noteUser($plan,$context,$depth,$onlyItems)
                    ."\nThe previous note was empty, shallow or structurally invalid. Rewrite the COMPLETE note with substantive learner-ready content. Do not return headings without explanations. Validation issues="
                    .json_encode($validation->errors(),JSON_UNESCAPED_UNICODE)
                    ."\nPREVIOUS_DRAFT=".json_encode($usage['data'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                $usage=$this->provider->generateStructured($this->prompts->noteSystem(),$repairPrompt,$this->schema->jsonSchema(),$depth==='detailed'?6500:4800);
                $content=$this->schema->validate($usage['data']);
            }
            $content['source_trace'] = $this->verifiedTrace($content['source_trace'] ?? [], $context);
            if ($onlyItems && ($previous = $plan->noteRevisions()->latest('revision')->first())) $content = $this->mergeMissing($previous->content, $content);
            $validation = $this->validator->validate($plan, $content, $context);

            return DB::transaction(function () use ($plan, $userId, $depth, $content, $validation) {
                $revisionNumber = ((int) LessonNoteRevision::where('lesson_plan_id', $plan->id)->lockForUpdate()->max('revision')) + 1;
                $revision = LessonNoteRevision::create(['tenant_id'=>$plan->tenant_id,'lesson_plan_id'=>$plan->id,'revision'=>$revisionNumber,
                    'status'=>'draft','depth'=>$depth,'content'=>$content,'source_trace'=>$content['source_trace'],'created_by'=>$userId]);
                LessonNoteValidation::create(array_merge($validation, ['tenant_id'=>$plan->tenant_id,'lesson_plan_id'=>$plan->id,'lesson_note_revision_id'=>$revision->id]));
                $plan->update(['current_note_revision'=>$revisionNumber,'note_depth'=>$depth,'lesson_notes'=>$this->renderer->toHtml($content)]);
                return $revision;
            });
        } catch (\Throwable $e) { $failure = $e; throw $e; }
        finally {
            AiUsageLog::create(['tenant_id'=>$plan->tenant_id,'user_id'=>$userId,'lesson_plan_id'=>$plan->id,'feature'=>'lesson_planner',
                'provider'=>$this->provider->name(),'model'=>$this->provider->model(),'request_type'=>$onlyItems ? 'regenerate_missing_sections' : 'generate_lesson_note',
                'input_tokens'=>$usage['input_tokens']??null,'output_tokens'=>$usage['output_tokens']??null,
                'total_tokens'=>isset($usage['input_tokens'],$usage['output_tokens']) ? $usage['input_tokens']+$usage['output_tokens'] : null,
                'status'=>$failure?'failed':'completed','latency_ms'=>(int)((hrtime(true)-$started)/1_000_000),'error_code'=>$failure?class_basename($failure):null]);
        }
    }

    private function verifiedTrace(array $trace, array $context): array
    {
        $allowed = collect($context)->keyBy(fn($item)=>(string)($item['fragment_id']??$item['evidence_id']??''));
        return collect($trace)->map(function ($item) use ($allowed) {
            $id = is_array($item) ? ($item['fragment_id'] ?? $item['evidence_id'] ?? null) : null;
            return $id && $allowed->has($id) ? $allowed->get($id) : null;
        })->filter()->unique(fn($item)=>$item['fragment_id']??$item['evidence_id']??null)->values()->all();
    }

    private function mergeMissing(array $existing, array $generated): array
    {
        $sections = collect($existing['sections'] ?? [])->keyBy(fn($s)=>mb_strtolower($s['heading'] ?? ''));
        foreach ($generated['sections'] ?? [] as $section) $sections->put(mb_strtolower($section['heading'] ?? uniqid()), $section);
        $existing['sections'] = $sections->values()->all();
        foreach (['key_examination_points','source_trace'] as $key) $existing[$key] = collect($existing[$key]??[])->merge($generated[$key]??[])->unique(fn($v)=>json_encode($v))->values()->all();
        foreach (['objective','structured','application'] as $type) $existing['review_questions'][$type] = collect($existing['review_questions'][$type]??[])->merge($generated['review_questions'][$type]??[])->unique()->values()->all();
        return $existing;
    }
}
