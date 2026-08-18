<?php

namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\LessonPlan;
use App\Models\Subject;
use App\Models\Term;
use App\Services\LessonAiService;
use App\Services\LessonPlanning\GroundedLessonNoteService;
use App\Services\LessonPlanning\StructuredLessonPlanService;
use App\Services\Curriculum\CurriculumRetrievalService;
use App\Models\CurriculumTopic;
use App\Models\LessonPlanSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonPlannerController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = LessonPlan::with(['subject', 'classLevel', 'classArm', 'term'])
            ->where('teacher_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('curriculum_type')) {
            $query->where('curriculum_type', $request->curriculum_type);
        }
        if ($request->filled('search')) {
            $query->where('topic', 'like', '%' . $request->search . '%');
        }

        $plans    = $query->paginate(20)->withQueryString();
        $subjects = Subject::active()->orderBy('name')->get();

        return view('lesson-planner.index', compact('plans', 'subjects'));
    }

    public function create()
    {
        $subjects    = Subject::active()->orderBy('name')->get();
        $classLevels = ClassLevel::orderBy('order_index')->get();
        $classArms   = ClassArm::with('classLevel')->orderBy('name')->get();
        $terms       = Term::orderByDesc('id')->take(9)->get();

        $curriculumTopics=CurriculumTopic::where('status','active')->get();return view('lesson-planner.create', compact('subjects', 'classLevels', 'classArms', 'terms','curriculumTopics'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_id'       => 'required|exists:subjects,id',
            'class_level_id'   => 'required|exists:class_levels,id',
            'class_arm_id'     => 'nullable|exists:class_arms,id',
            'term_id'          => 'nullable|exists:terms,id',
            'curriculum_type'  => 'required|in:nerdc,british',
            'curriculum_level_id'=>'nullable|exists:class_levels,id','delivery_type'=>'required|in:regular,carry_forward,remedial,revision,enrichment',
            'topic'            => 'required|string|max:255',
            'subtopic'         => 'nullable|string|max:255',
            'week_number'      => 'nullable|integer|min:1|max:52',
            'lesson_number'    => 'nullable|string|max:40',
            'lesson_time'      => 'nullable|string|max:40',
            'average_age'      => 'nullable|string|max:40',
            'sex'              => 'nullable|string|max:30',
            'plan_date'        => 'nullable|date',
            'duration_minutes' => 'required|integer|min:10|max:300',
            'status'           => 'required|in:draft,published',
            // NERDC sections
            'previous_knowledge'      => 'nullable|string',
            'behavioural_objectives'  => 'nullable|string',
            'instructional_materials' => 'nullable|string',
            'reference_materials'     => 'nullable|string',
            'set_induction'           => 'nullable|string',
            'presentation'            => 'nullable|string',
            'class_activity'          => 'nullable|string',
            'evaluation'              => 'nullable|string',
            'assignment'              => 'nullable|string',
            'conclusion'              => 'nullable|string',
            // British sections
            'learning_objectives'     => 'nullable|string',
            'success_criteria'        => 'nullable|string',
            'starter_activity'        => 'nullable|string',
            'differentiation'         => 'nullable|string',
            'plenary'                 => 'nullable|string',
            'assessment_for_learning' => 'nullable|string',
            'ai_generated'            => 'boolean',
            'structured_plan_json'    => 'nullable|json',
        ]);

        $data['teacher_id'] = Auth::id();

        if (!empty($data['structured_plan_json'])) $data['structured_plan'] = json_decode($data['structured_plan_json'], true);
        if (($data['curriculum_type'] ?? null) === 'nerdc') { $data['class_activity']=null; $data['conclusion']=null; }
        unset($data['structured_plan_json']);
        if (($data['status'] ?? 'draft') === 'published') $data['published_at'] = now();
        $plan = LessonPlan::create($data);
        $this->recordRepositorySources($plan);

        return redirect()->route('lesson-planner.show', $plan)
            ->with('success', 'Lesson plan saved successfully.');
    }

    public function show(LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);
        $lessonPlan->load(['subject', 'classLevel', 'classArm', 'term', 'teacher']);
        return view('lesson-planner.show', compact('lessonPlan'));
    }

    public function edit(LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);
        $subjects    = Subject::active()->orderBy('name')->get();
        $classLevels = ClassLevel::orderBy('order_index')->get();
        $classArms   = ClassArm::with('classLevel')->orderBy('name')->get();
        $terms       = Term::orderByDesc('id')->take(9)->get();

        $curriculumTopics=CurriculumTopic::where('status','active')->get();return view('lesson-planner.create', compact('lessonPlan', 'subjects', 'classLevels', 'classArms', 'terms','curriculumTopics'));
    }

    public function update(Request $request, LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);

        $data = $request->validate([
            'subject_id'       => 'required|exists:subjects,id',
            'class_level_id'   => 'required|exists:class_levels,id',
            'class_arm_id'     => 'nullable|exists:class_arms,id',
            'term_id'          => 'nullable|exists:terms,id',
            'curriculum_type'  => 'required|in:nerdc,british',
            'curriculum_level_id'=>'nullable|exists:class_levels,id','delivery_type'=>'required|in:regular,carry_forward,remedial,revision,enrichment',
            'topic'            => 'required|string|max:255',
            'subtopic'         => 'nullable|string|max:255',
            'week_number'      => 'nullable|integer|min:1|max:52',
            'lesson_number'    => 'nullable|string|max:40',
            'lesson_time'      => 'nullable|string|max:40',
            'average_age'      => 'nullable|string|max:40',
            'sex'              => 'nullable|string|max:30',
            'plan_date'        => 'nullable|date',
            'duration_minutes' => 'required|integer|min:10|max:300',
            'status'           => 'required|in:draft,published',
            'previous_knowledge'      => 'nullable|string',
            'behavioural_objectives'  => 'nullable|string',
            'instructional_materials' => 'nullable|string',
            'reference_materials'     => 'nullable|string',
            'set_induction'           => 'nullable|string',
            'presentation'            => 'nullable|string',
            'class_activity'          => 'nullable|string',
            'evaluation'              => 'nullable|string',
            'assignment'              => 'nullable|string',
            'conclusion'              => 'nullable|string',
            'learning_objectives'     => 'nullable|string',
            'success_criteria'        => 'nullable|string',
            'starter_activity'        => 'nullable|string',
            'differentiation'         => 'nullable|string',
            'plenary'                 => 'nullable|string',
            'assessment_for_learning' => 'nullable|string',
            'structured_plan_json'    => 'nullable|json',
        ]);

        if (!empty($data['structured_plan_json'])) $data['structured_plan'] = json_decode($data['structured_plan_json'], true);
        if (($data['curriculum_type'] ?? null) === 'nerdc') { $data['class_activity']=null; $data['conclusion']=null; }
        unset($data['structured_plan_json']);
        if (($data['status'] ?? 'draft') === 'published' && ! $lessonPlan->published_at) $data['published_at'] = now();
        if (($data['status'] ?? 'draft') === 'draft') $data['published_at'] = null;
        $lessonPlan->update($data);
        $lessonPlan->repositorySources()->delete();
        $this->recordRepositorySources($lessonPlan);

        return redirect()->route('lesson-planner.show', $lessonPlan)
            ->with('success', 'Lesson plan updated successfully.');
    }

    public function destroy(LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);
        $lessonPlan->delete();
        return redirect()->route('lesson-planner.index')
            ->with('success', 'Lesson plan deleted.');
    }

    // Ajax: generate content via AI
    public function generate(Request $request, StructuredLessonPlanService $structuredService, CurriculumRetrievalService $retrieval)
    {
        $request->validate([
            'subject'         => 'required|string',
            'class_level'     => 'required|string',
            'topic'           => 'required|string',
            'subtopic'        => 'nullable|string',
            'curriculum_type' => 'required|in:nerdc,british',
            'section'         => 'required|string', // junior_secondary | senior_secondary | primary | ks1 | ks2 etc.
            'term'            => 'nullable|string',
            'week'            => 'nullable|string',
            'duration_minutes'=> 'nullable|integer',
            'subject_id'=>'required|integer','teaching_class_id'=>'required|integer','curriculum_level_id'=>'nullable|integer','delivery_type'=>'required|in:regular,carry_forward,remedial,revision,enrichment',
        ]);

        try {
            $data = $request->only([
                'subject', 'class_level', 'topic', 'subtopic',
                'curriculum_type', 'section', 'term', 'week', 'duration_minutes',
            ]);

            if ($request->curriculum_type === 'british') {
                $result = app(LessonAiService::class)->generateBritishPlan($data);
            } else {
                $probe=new LessonPlan(['tenant_id'=>auth()->user()->tenant_id,'subject_id'=>$request->subject_id,'class_level_id'=>$request->teaching_class_id,'curriculum_level_id'=>$request->curriculum_level_id,'topic'=>$request->topic,'subtopic'=>$request->subtopic]);
                $context=$retrieval->compactContext($retrieval->forLessonPlan($probe));
                $structured = $structuredService->generate(array_merge($data, [
                    'lesson'=>$request->input('lesson_number','1'),'time'=>$request->input('lesson_time',''),
                    'average_age'=>$request->input('average_age',''),'sex'=>$request->input('sex','Mixed'),'curriculum_origin'=>$request->curriculum_level_id,
                    'delivery_type'=>$request->delivery_type,'repository_context'=>$context,
                ]));
                $result = $structuredService->legacyFields($structured);
                $result['structured_plan']['repository_context'] = $context;
            }

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // Print/PDF view
    public function print(LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);
        $lessonPlan->load(['subject', 'classLevel', 'classArm', 'term', 'teacher']);
        return view('lesson-planner.print', compact('lessonPlan'));
    }

    // Ajax: generate student notes via AI
    public function generateNotes(Request $request, LessonPlan $lessonPlan, GroundedLessonNoteService $service)
    {
        $this->authorise($lessonPlan);
        $lessonPlan->load(['subject', 'classLevel', 'classArm', 'term', 'teacher']);

        try {
            $depth = $request->validate(['depth' => 'nullable|in:concise,standard,detailed'])['depth'] ?? 'standard';
            $revision = $service->generate($lessonPlan, Auth::id(), $depth);
            $lessonPlan->refresh();
            return response()->json(['success' => true, 'notes' => $lessonPlan->lesson_notes, 'revision' => $revision->revision]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function regenerateMissing(LessonPlan $lessonPlan, GroundedLessonNoteService $service)
    {
        $this->authorise($lessonPlan);
        $latest = \App\Models\LessonNoteValidation::where('lesson_plan_id', $lessonPlan->id)->latest('id')->first();
        $items = $latest?->suggested_additions ?: [];
        if (! $items) return response()->json(['success'=>false,'message'=>'No missing content was identified.'], 422);
        try {
            $revision = $service->generate($lessonPlan, Auth::id(), $lessonPlan->note_depth ?: 'standard', $items);
            return response()->json(['success'=>true,'notes'=>$lessonPlan->fresh()->lesson_notes,'revision'=>$revision->revision]);
        } catch (\Throwable $e) { return response()->json(['success'=>false,'message'=>$e->getMessage()], 422); }
    }

    public function updateNote(Request $request, LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);$data=$request->validate(['note_text'=>'required|string|min:100','note_status'=>'required|in:draft,published']);
        $revision=$lessonPlan->noteRevisions()->latest('revision')->firstOrFail();$content=$revision->content;
        // Manual editing remains available without converting a rich, structured note into an invalid schema.
        $content['sections']=[['heading'=>$lessonPlan->topic,'subheading'=>null,'content_blocks'=>[['type'=>'paragraph','content'=>$data['note_text']]]]];
        $revision->update(['content'=>$content,'status'=>$data['note_status'],'teacher_edited'=>true]);
        $lessonPlan->update(['lesson_notes'=>'<h2>'.e($lessonPlan->topic).'</h2><p>'.nl2br(e($data['note_text'])).'</p>']);return back()->with('success','Student note saved as '.$data['note_status'].'.');
    }

    // Student notes view
    public function notes(LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);
        $lessonPlan->load(['subject', 'classLevel', 'classArm', 'term', 'teacher']);
        $revision = $lessonPlan->noteRevisions()->latest('revision')->first();
        $validation = $revision ? \App\Models\LessonNoteValidation::where('lesson_note_revision_id', $revision->id)->latest()->first() : null;
        return view('lesson-planner.notes', compact('lessonPlan', 'revision', 'validation'));
    }

    // Print student notes
    public function printNotes(LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);
        $lessonPlan->load(['subject', 'classLevel', 'classArm', 'term', 'teacher']);
        return view('lesson-planner.print-notes', compact('lessonPlan'));
    }

    private function authorise(LessonPlan $plan): void
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $plan->teacher_id !== $user->id) {
            abort(403);
        }
    }

    private function recordRepositorySources(LessonPlan $plan): void
    {
        foreach (($plan->structured_plan['repository_context'] ?? []) as $rank => $source) {
            if (!empty($source['fragment_id'])) {
                LessonPlanSource::create([
                    'lesson_plan_id' => $plan->id,
                    'curriculum_source_id' => $source['source_id'] ?? 0,
                    'curriculum_fragment_id' => $source['fragment_id'],
                    'rank' => $rank + 1,
                    'generation_type' => 'lesson_plan',
                ]);
            }
        }
    }
}
