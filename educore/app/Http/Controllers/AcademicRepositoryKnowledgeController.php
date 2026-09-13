<?php

namespace App\Http\Controllers;

use App\Models\AcademicTopic;
use App\Models\CurriculumSource;
use App\Services\AcademicRepositoryKnowledgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicRepositoryKnowledgeController extends Controller
{
    public function index(Request $request, AcademicRepositoryKnowledgeService $knowledge)
    {
        $this->guardReader();

        $topics = AcademicTopic::query()
            ->with(['blocks', 'source'])
            ->when($request->filled('class'), fn ($q) => $q->where('class_label', $request->string('class')->trim()->toString()))
            ->when($request->filled('subject'), fn ($q) => $q->where('subject_label', $request->string('subject')->trim()->toString()))
            ->when($request->filled('term'), fn ($q) => $q->where('term_label', $request->string('term')->trim()->toString()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%'.$request->string('search')->trim()->toString().'%';
                $q->where(fn ($w) => $w->where('topic', 'like', $search)->orWhere('sub_topic', 'like', $search));
            })
            ->orderBy('class_label')->orderBy('subject_label')->orderBy('term_label')->orderBy('week_number')->orderBy('lesson_number')
            ->get();

        $readiness = $topics->mapWithKeys(fn ($topic) => [$topic->id => $knowledge->readiness($topic)]);
        $filters = [
            'classes' => AcademicTopic::query()->distinct()->orderBy('class_label')->pluck('class_label'),
            'subjects' => AcademicTopic::query()->distinct()->orderBy('subject_label')->pluck('subject_label'),
            'terms' => AcademicTopic::query()->distinct()->orderBy('term_label')->pluck('term_label'),
        ];

        return view('academic-repository.knowledge.index', compact('topics', 'readiness', 'filters'));
    }

    public function create()
    {
        $this->guardAdmin();

        return view('academic-repository.knowledge.form', [
            'topic' => new AcademicTopic(),
            'sources' => $this->sources(),
            'blockText' => [],
        ]);
    }

    public function store(Request $request)
    {
        $this->guardAdmin();
        $data = $this->validated($request);

        $topic = DB::transaction(function () use ($data, $request) {
            $topic = AcademicTopic::create($data);
            $this->syncBlocks($topic, $request);
            return $topic;
        });

        return redirect()->route('academic-repository.knowledge.show', $topic)->with('success', 'Curriculum topic added to the knowledge base.');
    }

    public function show(AcademicTopic $academicTopic, AcademicRepositoryKnowledgeService $knowledge)
    {
        $this->guardReader();
        $academicTopic->load(['blocks', 'source']);

        return view('academic-repository.knowledge.show', [
            'topic' => $academicTopic,
            'readiness' => $knowledge->readiness($academicTopic),
        ]);
    }

    public function edit(AcademicTopic $academicTopic)
    {
        $this->guardAdmin();
        $academicTopic->load('blocks');

        return view('academic-repository.knowledge.form', [
            'topic' => $academicTopic,
            'sources' => $this->sources(),
            'blockText' => $academicTopic->blocks->groupBy('block_type')->map(fn ($blocks) => $blocks->pluck('content')->implode("\n\n"))->all(),
        ]);
    }

    public function update(Request $request, AcademicTopic $academicTopic)
    {
        $this->guardAdmin();
        $data = $this->validated($request);

        DB::transaction(function () use ($academicTopic, $data, $request) {
            $academicTopic->update($data);
            $this->syncBlocks($academicTopic, $request);
        });

        return redirect()->route('academic-repository.knowledge.show', $academicTopic)->with('success', 'Knowledge record updated.');
    }

    public function approve(AcademicTopic $academicTopic)
    {
        $this->guardAdmin();
        $academicTopic->load('blocks');

        $academicTopic->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        $academicTopic->blocks()->update(['is_approved' => true]);

        return back()->with('success', 'Topic approved for deterministic generation.');
    }

    public function generate(AcademicTopic $academicTopic, string $type, AcademicRepositoryKnowledgeService $knowledge)
    {
        $this->guardReader();
        abort_unless(in_array($type, ['lesson-plan', 'student-note'], true), 404);
        abort_unless($academicTopic->status === 'approved', 422, 'This topic must be approved before official content can be generated.');

        $readiness = $knowledge->readiness($academicTopic);
        abort_unless($readiness['ready'], 422, 'This topic is not generation-ready. Complete the missing knowledge fields first.');

        $document = $type === 'lesson-plan'
            ? $knowledge->lessonPlan($academicTopic)
            : $knowledge->studentNote($academicTopic);

        return view('academic-repository.knowledge.generated', compact('academicTopic', 'type', 'document', 'readiness'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'curriculum_source_id' => ['nullable', 'integer', Rule::exists('curriculum_sources', 'id')],
            'class_label' => ['required', 'string', 'max:120'],
            'subject_label' => ['required', 'string', 'max:160'],
            'term_label' => ['required', 'string', 'max:80'],
            'week_number' => ['nullable', 'integer', 'min:1', 'max:60'],
            'lesson_number' => ['nullable', 'integer', 'min:1', 'max:20'],
            'topic' => ['required', 'string', 'max:255'],
            'sub_topic' => ['nullable', 'string', 'max:255'],
            'resource_type' => ['required', Rule::in(['curriculum', 'scheme_of_work', 'syllabus', 'lesson_note', 'teacher_note', 'textbook_extract', 'practical_guide', 'past_questions', 'lesson_plan', 'other'])],
            'entry_behaviour' => ['nullable', 'string'],
            'previous_knowledge' => ['nullable', 'string'],
            'instructional_resources' => ['nullable', 'string'],
            'introduction' => ['nullable', 'string'],
            'reference' => ['nullable', 'string'],
            'student_note_summary' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'review', 'approved'])],
        ]);
    }

    private function syncBlocks(AcademicTopic $topic, Request $request): void
    {
        $types = ['objective', 'presentation', 'definition', 'explanation', 'example', 'practical', 'note', 'evaluation', 'assignment'];
        $topic->blocks()->delete();

        foreach ($types as $type) {
            $chunks = preg_split('/\R\s*\R/u', trim((string) $request->input('blocks.'.$type, ''))) ?: [];
            $sequence = 1;
            foreach ($chunks as $chunk) {
                $chunk = trim($chunk);
                if ($chunk === '') continue;

                $title = null;
                $content = $chunk;
                if ($type === 'presentation' && str_contains($chunk, '::')) {
                    [$title, $content] = array_map('trim', explode('::', $chunk, 2));
                }

                $topic->blocks()->create([
                    'block_type' => $type,
                    'sequence' => $sequence++,
                    'title' => $title ?: null,
                    'content' => $content,
                    'is_required' => in_array($type, AcademicRepositoryKnowledgeService::REQUIRED_BLOCK_TYPES, true),
                    'is_approved' => $topic->status === 'approved',
                ]);
            }
        }
    }

    private function sources()
    {
        return CurriculumSource::query()
            ->whereNull('tenant_id')
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'original_filename', 'metadata']);
    }

    private function guardReader(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403);
    }

    private function guardAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Only school administrators can curate the academic knowledge base.');
    }
}
