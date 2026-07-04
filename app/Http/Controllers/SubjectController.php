<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\ClassLevel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function tenantTeacherRule()
    {
        return Rule::exists('users', 'id')->where(fn ($query) => $query
            ->where('tenant_id', $this->tenantId())
            ->where('is_super_admin', false)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('employment_status')->orWhere('employment_status', User::STAFF_STATUS_ACTIVE))
            ->whereIn('role', User::teachingRoleNames()));
    }

    // ---------------------------------------------------------------
    // SUBJECT LIST
    // ---------------------------------------------------------------
    public function index(Request $request)
    {
        $query = Subject::withCount(['scores', 'classArms']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $subjects = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('subjects.index', compact('subjects'));
    }

    // ---------------------------------------------------------------
    // CREATE SUBJECT
    // ---------------------------------------------------------------
    public function create()
    {
        return view('subjects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        // Check for duplicate name within tenant
        $exists = Subject::where('name', $validated['name'])->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'A subject with this name already exists.']);
        }

        Subject::create($validated);
        return redirect()->route('subjects.index')->with('success', 'Subject created successfully.');
    }

    // ---------------------------------------------------------------
    // SUBJECT DETAIL — Class assignments
    // ---------------------------------------------------------------
    public function show(Subject $subject)
    {
        $assignments  = ClassArmSubject::where('subject_id', $subject->id)
                                       ->with(['classArm.classLevel', 'teacher', 'session'])
                                       ->get();
        $classArms    = ClassArm::with('classLevel')->get();
        $classLevels  = ClassLevel::orderBy('order_index')->get();
        $teachers     = User::activeStaff($this->tenantId())->teachers()->orderBy('name')->get();
        $sessions     = AcademicSession::orderByDesc('is_current')->get();

        return view('subjects.show', compact('subject', 'assignments', 'classArms', 'classLevels', 'teachers', 'sessions'));
    }

    // ---------------------------------------------------------------
    // EDIT SUBJECT
    // ---------------------------------------------------------------
    public function edit(Subject $subject)
    {
        return view('subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $subject->update($validated);

        return redirect()->route('subjects.show', $subject)
                         ->with('success', 'Subject updated.');
    }

    // ---------------------------------------------------------------
    // ASSIGN SUBJECT TO CLASS
    // ---------------------------------------------------------------
    public function assign(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'teacher_id'   => ['nullable', $this->tenantTeacherRule()],
            'session_id'   => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $exists = ClassArmSubject::where([
            'subject_id'   => $subject->id,
            'class_arm_id' => $validated['class_arm_id'],
            'session_id'   => $validated['session_id'],
        ])->exists();

        if ($exists) {
            return back()->withErrors(['class_arm_id' => 'This subject is already assigned to that class for the selected session.']);
        }

        ClassArmSubject::create(array_merge($validated, ['subject_id' => $subject->id]));

        return back()->with('success', 'Subject assigned to class.');
    }

    // ---------------------------------------------------------------
    // REMOVE ASSIGNMENT
    // ---------------------------------------------------------------
    public function removeAssignment(ClassArmSubject $assignment)
    {
        $subjectId = $assignment->subject_id;
        $assignment->delete();
        return redirect()->route('subjects.show', $subjectId)
                         ->with('success', 'Assignment removed.');
    }

    // ---------------------------------------------------------------
    // TOGGLE ACTIVE STATUS
    // ---------------------------------------------------------------
    public function toggle(Subject $subject)
    {
        $subject->update(['is_active' => !$subject->is_active]);
        return back()->with('success', 'Subject ' . ($subject->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function destroy(\App\Models\Subject $subject)
    {
        if ($subject->classArmSubjects()->count()) {
            return back()->withErrors(['error' => 'Cannot delete subject assigned to classes. Remove from all classes first.']);
        }
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Subject deleted.');
    }

}
