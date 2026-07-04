<?php

namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\LessonPlan;
use App\Models\Subject;
use App\Models\Term;
use App\Services\LessonAiService;
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
        ]);

        $data['teacher_id'] = Auth::id();

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
        ]);

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
    public function generate(Request $request)
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
            $service = new LessonAiService();
            $data = $request->only([
                'subject', 'class_level', 'topic', 'subtopic',
                'curriculum_type', 'section', 'term', 'week', 'duration_minutes',
            ]);

            $result = $request->curriculum_type === 'british'
                ? $service->generateBritishPlan($data)
                : $service->generateNerdcPlan($data);

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
    public function generateNotes(LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);
        $lessonPlan->load(['subject', 'classLevel', 'classArm', 'term', 'teacher']);

        try {
            $service = new LessonAiService();
            $notes   = $service->generateStudentNotes($lessonPlan);
            $lessonPlan->update(['lesson_notes' => $notes]);
            return response()->json(['success' => true, 'notes' => $notes]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // Student notes view
    public function notes(LessonPlan $lessonPlan)
    {
        $this->authorise($lessonPlan);
        $lessonPlan->load(['subject', 'classLevel', 'classArm', 'term', 'teacher']);
        return view('lesson-planner.notes', compact('lessonPlan'));
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
