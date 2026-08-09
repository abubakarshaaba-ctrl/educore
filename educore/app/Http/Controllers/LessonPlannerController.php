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

        return view('lesson-planner.create', compact('subjects', 'classLevels', 'classArms', 'terms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_id'       => 'required|exists:subjects,id',
            'class_level_id'   => 'required|exists:class_levels,id',
            'class_arm_id'     => 'nullable|exists:class_arms,id',
            'term_id'          => 'nullable|exists:terms,id',
            'curriculum_type'  => 'required|in:nerdc,british',
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
            'entry_behaviour'         => 'nullable|string',
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
        unset($data['structured_plan_json']);
        if (($data['status'] ?? 'draft') === 'published') { $data['approved_at'] = now(); $data['approved_by'] = Auth::id(); }
        $plan = LessonPlan::create($data);

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

        return view('lesson-planner.create', compact('lessonPlan', 'subjects', 'classLevels', 'classArms', 'terms'));
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
            'entry_behaviour'         => 'nullable|string',
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
        unset($data['structured_plan_json']);
        if (($data['status'] ?? 'draft') === 'published' && ! $lessonPlan->approved_at) { $data['approved_at'] = now(); $data['approved_by'] = Auth::id(); }
        if (($data['status'] ?? 'draft') === 'draft') { $data['approved_at'] = null; $data['approved_by'] = null; }
        $lessonPlan->update($data);

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
    public function generate(Request $request, StructuredLessonPlanService $structuredService)
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
        ]);

        try {
            $data = $request->only([
                'subject', 'class_level', 'topic', 'subtopic',
                'curriculum_type', 'section', 'term', 'week', 'duration_minutes',
            ]);

            if ($request->curriculum_type === 'british') {
                $result = app(LessonAiService::class)->generateBritishPlan($data);
            } else {
                $structured = $structuredService->generate(array_merge($data, [
                    'lesson'=>$request->input('lesson_number','1'),'time'=>$request->input('lesson_time',''),
                    'average_age'=>$request->input('average_age',''),'sex'=>$request->input('sex','Mixed'),
                ]));
                $result = $structuredService->legacyFields($structured);
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

    public function approve(LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);
        $lessonPlan->update(['status' => 'published', 'approved_at' => now(), 'approved_by' => Auth::id()]);
        return back()->with('success', 'Lesson plan approved. Curriculum-grounded lesson notes can now be generated.');
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

    public function approveNote(LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);
        $revision=$lessonPlan->noteRevisions()->latest('revision')->firstOrFail();
        $revision->update(['status'=>'approved','approved_by'=>Auth::id(),'approved_at'=>now()]);
        return back()->with('success','Lesson note approved. This revision is preserved for printing and audit.');
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
}
