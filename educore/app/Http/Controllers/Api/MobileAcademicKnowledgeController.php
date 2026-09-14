<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicTopic;
use App\Services\AcademicRepositoryKnowledgeService;
use App\Services\AcademicTopicLessonPlanService;
use App\Services\AcademicTopicStudentNoteService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class MobileAcademicKnowledgeController extends Controller
{
    public function __construct(
        private readonly AcademicRepositoryKnowledgeService $knowledge,
        private readonly AcademicTopicLessonPlanService $lessonPlans,
        private readonly AcademicTopicStudentNoteService $studentNotes,
    ) {}

    public function index(Request $request)
    {
        $this->guardReader($request);
        if ($response = $this->schemaUnavailableResponse()) return $response;

        $data = $request->validate([
            'class' => ['nullable','string','max:100'],
            'term' => ['nullable','string','max:100'],
            'subject' => ['nullable','string','max:150'],
            'query' => ['nullable','string','max:150'],
            'ready' => ['nullable','boolean'],
            'page' => ['nullable','integer','min:1'],
            'per_page' => ['nullable','integer','min:1','max:100'],
        ]);

        $query = AcademicTopic::query()->with(['source','blocks']);
        foreach (['class_label'=>'class','term_label'=>'term','subject_label'=>'subject'] as $column => $key) {
            if (filled($data[$key] ?? null)) $query->where($column, $data[$key]);
        }
        if (filled($data['query'] ?? null)) {
            $needle = '%'.trim($data['query']).'%';
            $query->where(fn($q) => $q->where('topic','like',$needle)->orWhere('sub_topic','like',$needle));
        }

        $items = $query->orderBy('class_label')->orderBy('subject_label')->orderBy('term_label')->orderBy('week_number')->orderBy('lesson_number')->get();
        $rows = $items->map(fn(AcademicTopic $topic) => $this->topicPayload($topic));
        if (array_key_exists('ready', $data)) $rows = $rows->where('readiness.ready', (bool)$data['ready'])->values();

        $page = (int)($data['page'] ?? 1);
        $perPage = (int)($data['per_page'] ?? 30);
        $paginator = new LengthAwarePaginator($rows->forPage($page,$perPage)->values(), $rows->count(), $perPage, $page, ['path'=>$request->url(),'query'=>$request->query()]);

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'topics' => $paginator->items(),
            'meta' => [
                'current_page'=>$paginator->currentPage(), 'last_page'=>$paginator->lastPage(),
                'per_page'=>$paginator->perPage(), 'total'=>$paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, AcademicTopic $topic)
    {
        $this->guardReader($request);
        if ($response = $this->schemaUnavailableResponse()) return $response;
        $topic->load(['source','blocks']);
        return response()->json(['contract_version'=>1,'generated_at'=>now()->toIso8601String(),'topic'=>$this->topicPayload($topic, true)]);
    }

    public function generate(Request $request, AcademicTopic $topic, string $type)
    {
        $this->guardReader($request);
        if ($response = $this->schemaUnavailableResponse()) return $response;
        abort_unless(in_array($type, ['lesson-plan','student-note'], true), 404);
        $topic->load(['source','blocks']);
        $readiness = $this->knowledge->readiness($topic);
        abort_unless($readiness['ready'], 422, 'This topic is not generation-ready.');
        $document = $type === 'lesson-plan' ? $this->knowledge->lessonPlan($topic) : $this->knowledge->studentNote($topic);

        return response()->json([
            'contract_version'=>1,
            'generated_at'=>now()->toIso8601String(),
            'type'=>$type,
            'topic'=>$this->topicPayload($topic, true),
            'document'=>$this->mobileDocument($type, $document),
        ]);
    }

    public function saveLessonPlan(Request $request, AcademicTopic $topic)
    {
        $this->guardReader($request);
        if ($response = $this->schemaUnavailableResponse()) return $response;
        $plan = $this->lessonPlans->save($topic, $request->user());
        return response()->json(['message'=>'Lesson plan saved to Lesson Planner.','lesson_plan_id'=>$plan->id]);
    }

    public function saveStudentNote(Request $request, AcademicTopic $topic)
    {
        $this->guardReader($request);
        if ($response = $this->schemaUnavailableResponse()) return $response;
        $revision = $this->studentNotes->save($topic, $request->user());
        return response()->json(['message'=>'Student note saved to Lesson Planner.','lesson_plan_id'=>$revision->lesson_plan_id,'revision'=>$revision->revision]);
    }

    private function topicPayload(AcademicTopic $topic, bool $full = false): array
    {
        $readiness = $this->knowledge->readiness($topic);
        $payload = [
            'id'=>$topic->id, 'class'=>$topic->class_label, 'subject'=>$topic->subject_label,
            'term'=>$topic->term_label, 'week'=>$topic->week_number, 'lesson'=>$topic->lesson_number,
            'topic'=>$topic->topic, 'sub_topic'=>$topic->sub_topic, 'resource_type'=>$topic->resource_type,
            'readiness'=>[
                'score'=>$readiness['score'], 'coverage_score'=>$readiness['coverage_score'] ?? $readiness['score'],
                'quality_score'=>$readiness['quality_score'] ?? null, 'ready'=>$readiness['ready'],
                'missing'=>$readiness['missing'] ?? [], 'critical'=>$readiness['quality']['critical'] ?? [],
            ],
            'consolidation'=>$readiness['consolidation'] ?? null,
            'sources'=>$readiness['sources'] ?? [],
        ];
        if ($full) {
            $payload['fields'] = [
                'time'=>$topic->lesson_time, 'duration'=>$topic->duration_minutes, 'average_age'=>$topic->average_age,
                'sex'=>$topic->sex, 'entry_behaviour'=>$topic->entry_behaviour, 'previous_knowledge'=>$topic->previous_knowledge,
                'instructional_resources'=>$topic->instructional_resources, 'introduction'=>$topic->introduction,
                'student_note_summary'=>$topic->student_note_summary, 'reference'=>$topic->reference,
            ];
            $payload['blocks'] = $topic->blocks->map(fn($block)=>[
                'type'=>$block->block_type,'sequence'=>$block->sequence,'title'=>$block->title,'content'=>$block->content,
            ])->values();
        }
        return $payload;
    }

    private function mobileDocument(string $type, array $document): array
    {
        $sections = [];
        if ($type === 'lesson-plan') {
            $sections[] = ['heading'=>'Topic', 'content'=>(string)($document['topic'] ?? '')];
            if (filled($document['sub_topic'] ?? null)) $sections[] = ['heading'=>'Sub-topic', 'content'=>(string)$document['sub_topic']];
            foreach ([
                'Entry Behaviour'=>'entry_behaviour', 'Previous / Background Knowledge'=>'previous_knowledge',
                'Instructional Resources'=>'instructional_resources', 'Introduction'=>'introduction',
            ] as $heading => $key) {
                if (filled($document[$key] ?? null)) $sections[] = ['heading'=>$heading,'content'=>(string)$document[$key]];
            }
            if (!empty($document['behavioural_objectives'])) $sections[] = ['heading'=>'Behavioural Objectives','content'=>$this->numbered($document['behavioural_objectives'])];
            foreach ($document['presentation'] ?? [] as $step) {
                $sections[] = ['heading'=>(string)($step['title'] ?? 'Presentation'), 'content'=>(string)($step['content'] ?? '')];
            }
            if (!empty($document['evaluation'])) $sections[] = ['heading'=>'Evaluation','content'=>$this->numbered($document['evaluation'])];
            if (!empty($document['assignment'])) $sections[] = ['heading'=>'Assignment','content'=>$this->numbered($document['assignment'])];
            if (filled($document['reference'] ?? null)) $sections[] = ['heading'=>'Reference','content'=>(string)$document['reference']];
        } else {
            if (!empty($document['objectives'])) $sections[] = ['heading'=>'Learning Objectives','content'=>$this->numbered($document['objectives'])];
            if (filled($document['summary'] ?? null)) $sections[] = ['heading'=>'Overview','content'=>(string)$document['summary']];
            foreach ($document['content'] ?? [] as $section) {
                $sections[] = ['heading'=>(string)($section['heading'] ?? 'Lesson Content'), 'content'=>(string)($section['content'] ?? '')];
            }
            if (!empty($document['review_questions'])) $sections[] = ['heading'=>'Review Questions','content'=>$this->numbered($document['review_questions'])];
            if (!empty($document['assignment'])) $sections[] = ['heading'=>'Assignment','content'=>$this->numbered($document['assignment'])];
            if (filled($document['reference'] ?? null)) $sections[] = ['heading'=>'Reference','content'=>(string)$document['reference']];
        }

        return ['title'=>(string)($document['topic'] ?? 'Academic Content'), 'sections'=>$sections];
    }

    private function numbered(array $items): string
    {
        return collect($items)->values()->map(fn($item, $i) => ($i+1).'. '.trim((string)$item))->implode("\n");
    }

    private function schemaUnavailableResponse()
    {
        if (Schema::hasTable('academic_topics') && Schema::hasTable('academic_topic_blocks')) {
            return null;
        }

        return response()->json([
            'message' => 'Curriculum Knowledge is being initialized. Please retry after the latest server update completes.',
            'code' => 'academic_knowledge_initializing',
        ], 503);
    }

    private function guardReader(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403, 'Curriculum Knowledge Base access is limited to school administrators and teachers.');
    }
}
