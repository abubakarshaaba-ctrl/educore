<?php

namespace App\Http\Controllers;

use App\Models\AcademicTrack;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\ClassLevelSubject;
use App\Models\Student;
use App\Models\StudentSubjectSelection;
use App\Models\Subject;
use App\Models\ClassArmSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CurriculumController extends Controller
{
    private function tid(): int
    {
        return auth()->user()->tenant_id;
    }

    private function tenantExists(string $table): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists($table, 'id')->where('tenant_id', $this->tid());
    }

    private function tenantOrSystemTrackRule(): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('academic_tracks', 'id')->where(fn ($query) => $query
            ->where(fn ($trackQuery) => $trackQuery
                ->whereNull('tenant_id')
                ->orWhere('tenant_id', $this->tid())));
    }

    private function tenantTeacherRule(): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('users', 'id')->where(fn ($query) => $query
            ->where('tenant_id', $this->tid())
            ->where('is_super_admin', false)
            ->whereIn('role', \App\Models\User::teachingRoleNames()));
    }

    // ═══════════════════════════════════════════════════════════════════
    // ACADEMIC TRACKS
    // ═══════════════════════════════════════════════════════════════════

    public function tracks()
    {
        $tracks = AcademicTrack::forTenant($this->tid())->withCount('classArms')->get();
        return view('curriculum.tracks', compact('tracks'));
    }

    public function storeTracks(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:80'],
            'section'    => ['required', 'in:junior,senior,general'],
            'is_active'  => ['boolean'],
        ]);

        $data['tenant_id']  = $this->tid();
        $data['slug']       = \Illuminate\Support\Str::slug($data['name']) . '-' . $this->tid();
        $data['sort_order'] = AcademicTrack::forTenant($this->tid())->count() + 1;
        $data['is_active']  = $data['is_active'] ?? true;

        AcademicTrack::create($data);
        return back()->with('success', "Track '{$data['name']}' created.");
    }

    public function toggleTrack(AcademicTrack $track)
    {
        $track->update(['is_active' => !$track->is_active]);
        return back()->with('success', "Track " . ($track->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function destroyTrack(AcademicTrack $track)
    {
        // Only destroy tenant-specific tracks, not system defaults
        if ($track->tenant_id !== $this->tid()) abort(403);
        $track->delete();
        return back()->with('success', 'Track deleted.');
    }

    // ═══════════════════════════════════════════════════════════════════
    // CLASS LEVEL SUBJECT RULES
    // ═══════════════════════════════════════════════════════════════════

    public function levelSubjects(ClassLevel $level, Request $request)
    {
        $tracks      = AcademicTrack::forTenant($this->tid())->active()->get();
        $trackId     = $request->integer('track_id') ?: null;
        $allSubjects = Subject::where('tenant_id', $this->tid())->active()->orderBy('name')->get();

        // Current rules for this level + track
        $rules = ClassLevelSubject::where('tenant_id', $this->tid())
            ->where('class_level_id', $level->id)
            ->when($trackId, fn($q) =>
                $q->where(function ($q2) use ($trackId) {
                    $q2->whereNull('academic_track_id')->orWhere('academic_track_id', $trackId);
                }),
                fn($q) => $q->whereNull('academic_track_id')
            )
            ->with(['subject', 'academicTrack'])
            ->orderBy('subject_status')
            ->get();

        // Group by status for display
        $byStatus = $rules->groupBy('subject_status');

        // Subjects not yet assigned
        $assignedIds    = $rules->pluck('subject_id')->unique();
        $unassigned     = $allSubjects->whereNotIn('id', $assignedIds);

        return view('curriculum.level-subjects', compact(
            'level', 'tracks', 'trackId', 'allSubjects',
            'rules', 'byStatus', 'unassigned'
        ));
    }

    public function storeLevelSubject(Request $request, ClassLevel $level)
    {
        $data = $request->validate([
            'subject_id'        => ['required', $this->tenantExists('subjects')],
            'academic_track_id' => ['nullable', $this->tenantOrSystemTrackRule()],
            'subject_status'    => ['required', 'in:compulsory,elective,optional,not_offered'],
            'elective_group'    => ['nullable', 'string', 'max:80'],
            'min_required'      => ['nullable', 'integer', 'min:0'],
            'max_allowed'       => ['nullable', 'integer', 'min:0'],
        ]);

        $data['tenant_id']      = $this->tid();
        $data['class_level_id'] = $level->id;
        $data['is_active']      = true;

        // Upsert: update if already exists, insert if not
        ClassLevelSubject::updateOrCreate(
            [
                'tenant_id'        => $data['tenant_id'],
                'class_level_id'   => $data['class_level_id'],
                'academic_track_id'=> $data['academic_track_id'],
                'subject_id'       => $data['subject_id'],
            ],
            $data
        );

        $subject = Subject::find($data['subject_id']);
        return back()->with('success', "'{$subject->name}' set as {$data['subject_status']}.");
    }

    public function updateLevelSubject(Request $request, ClassLevelSubject $rule)
    {
        $data = $request->validate([
            'subject_status' => ['required', 'in:compulsory,elective,optional,not_offered'],
            'elective_group' => ['nullable', 'string', 'max:80'],
            'min_required'   => ['nullable', 'integer', 'min:0'],
            'max_allowed'    => ['nullable', 'integer', 'min:0'],
            'is_active'      => ['boolean'],
        ]);

        $rule->update($data);
        return back()->with('success', 'Subject rule updated.');
    }

    public function destroyLevelSubject(ClassLevelSubject $rule)
    {
        $rule->delete();
        return back()->with('success', 'Subject rule removed.');
    }

    /**
     * Bulk update — apply a set of subjects to a level/track at once.
     */
    public function bulkSetLevelSubjects(Request $request, ClassLevel $level)
    {
        $data = $request->validate([
            'academic_track_id'     => ['nullable', $this->tenantOrSystemTrackRule()],
            'assignments'           => ['required', 'array'],
            'assignments.*.subject_id'     => ['required', $this->tenantExists('subjects')],
            'assignments.*.subject_status' => ['required', 'in:compulsory,elective,optional,not_offered'],
            'assignments.*.elective_group' => ['nullable', 'string', 'max:80'],
        ]);

        $tid     = $this->tid();
        $trackId = $data['academic_track_id'] ?? null;
        $count   = 0;

        foreach ($data['assignments'] as $a) {
            ClassLevelSubject::updateOrCreate(
                [
                    'tenant_id'         => $tid,
                    'class_level_id'    => $level->id,
                    'academic_track_id' => $trackId,
                    'subject_id'        => $a['subject_id'],
                ],
                [
                    'subject_status' => $a['subject_status'],
                    'elective_group' => $a['elective_group'] ?? null,
                    'is_active'      => true,
                ]
            );
            $count++;
        }

        return back()->with('success', "{$count} subject rules saved for {$level->name}.");
    }

    // ═══════════════════════════════════════════════════════════════════
    // CLASS ARM TRACK ASSIGNMENT
    // ═══════════════════════════════════════════════════════════════════

    public function armTrackAssignment()
    {
        $tracks = AcademicTrack::forTenant($this->tid())->active()->get();
        $arms   = ClassArm::with(['classLevel', 'academicTrack'])
                    ->where('tenant_id', $this->tid())
                    ->get()
                    ->groupBy('class_level_id');

        $levels = ClassLevel::where('tenant_id', $this->tid())
                    ->orderBy('order_index')
                    ->get();

        return view('curriculum.arm-tracks', compact('tracks', 'arms', 'levels'));
    }

    public function setArmTrack(Request $request, ClassArm $arm)
    {
        $data = $request->validate([
            'academic_track_id' => ['nullable', $this->tenantOrSystemTrackRule()],
        ]);

        $arm->update(['academic_track_id' => $data['academic_track_id'] ?: null]);

        $track = $data['academic_track_id']
            ? AcademicTrack::find($data['academic_track_id'])?->name
            : 'General (no track)';

        return back()->with('success', "{$arm->full_name} assigned to {$track}.");
    }

    // ═══════════════════════════════════════════════════════════════════
    // STUDENT SUBJECT SELECTION
    // ═══════════════════════════════════════════════════════════════════

    public function studentSubjects(Student $student, Request $request)
    {
        $arm     = $student->currentClassArm?->load(['classLevel', 'academicTrack']);
        $session = \App\Models\AcademicSession::where('is_current', true)->first();

        if (!$arm) {
            return back()->withErrors(['error' => 'Student is not assigned to a class.']);
        }

        $levelId = $arm->class_level_id;
        $trackId = $arm->academic_track_id;

        // All rules for level + track
        $allRules = ClassLevelSubject::where('tenant_id', $this->tid())
            ->where('class_level_id', $levelId)
            ->where('is_active', true)
            ->where('subject_status', '!=', 'not_offered')
            ->where(function ($q) use ($trackId) {
                $q->whereNull('academic_track_id');
                if ($trackId) $q->orWhere('academic_track_id', $trackId);
            })
            ->with(['subject', 'academicTrack'])
            ->orderBy('subject_status')
            ->get();

        $compulsoryRules = $allRules->where('subject_status', 'compulsory');
        $electiveRules   = $allRules->whereIn('subject_status', ['elective', 'optional']);

        // Current student selections
        $selections = StudentSubjectSelection::where('tenant_id', $this->tid())
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->when($session, fn($q) => $q->where('session_id', $session->id))
            ->pluck('subject_id')
            ->flip();  // flip for O(1) lookup

        // Elective groups
        $electiveGroups = $electiveRules->groupBy('elective_group');

        return view('curriculum.student-subjects', compact(
            'student', 'arm', 'session',
            'compulsoryRules', 'electiveRules', 'electiveGroups', 'selections'
        ));
    }

    public function syncCompulsoryForStudent(Student $student)
    {
        $session = \App\Models\AcademicSession::where('is_current', true)->first();
        $count   = $student->syncCompulsorySubjects($session?->id);

        return back()->with('success', "{$count} compulsory subject(s) synced for {$student->full_name}.");
    }

    public function addStudentElective(Request $request, Student $student)
    {
        $data = $request->validate([
            'subject_id' => ['required', $this->tenantExists('subjects')],
            'session_id' => ['nullable', $this->tenantExists('academic_sessions')],
        ]);

        $arm     = $student->currentClassArm;
        if (!$arm) return back()->withErrors(['error' => 'Student not in a class.']);

        $levelId = $arm->class_level_id;
        $trackId = $arm->academic_track_id;
        $tid     = $this->tid();

        // Validate: subject must be elective/optional for this level+track
        $rule = ClassLevelSubject::where('tenant_id', $tid)
            ->where('class_level_id', $levelId)
            ->where('subject_id', $data['subject_id'])
            ->where('is_active', true)
            ->whereIn('subject_status', ['elective', 'optional'])
            ->where(function ($q) use ($trackId) {
                $q->whereNull('academic_track_id');
                if ($trackId) $q->orWhere('academic_track_id', $trackId);
            })->first();

        if (!$rule) {
            return back()->withErrors(['error' => 'This subject is not an available elective for this student\'s class level and track.']);
        }

        // Check max_allowed in elective group
        if ($rule->elective_group && $rule->max_allowed) {
            $groupSubjectIds = ClassLevelSubject::where('tenant_id', $tid)
                ->where('class_level_id', $levelId)
                ->where('elective_group', $rule->elective_group)
                ->pluck('subject_id');

            $existingCount = StudentSubjectSelection::where('tenant_id', $tid)
                ->where('student_id', $student->id)
                ->where('is_active', true)
                ->whereIn('subject_id', $groupSubjectIds)
                ->count();

            if ($existingCount >= $rule->max_allowed) {
                return back()->withErrors([
                    'error' => "Maximum of {$rule->max_allowed} subjects allowed from group '{$rule->elective_group}'."
                ]);
            }
        }

        $session = $data['session_id']
            ? \App\Models\AcademicSession::find($data['session_id'])
            : \App\Models\AcademicSession::where('is_current', true)->first();

        StudentSubjectSelection::updateOrCreate(
            [
                'tenant_id'  => $tid,
                'student_id' => $student->id,
                'subject_id' => $data['subject_id'],
                'session_id' => $session?->id,
            ],
            [
                'class_level_id'    => $levelId,
                'academic_track_id' => $trackId,
                'selection_type'    => 'elective',
                'is_active'         => true,
            ]
        );

        $subject = Subject::find($data['subject_id']);
        return back()->with('success', "'{$subject->name}' added as elective for {$student->full_name}.");
    }

    public function removeStudentElective(Request $request, Student $student)
    {
        $data = $request->validate([
            'subject_id' => ['required', $this->tenantExists('subjects')],
            'session_id' => ['nullable', $this->tenantExists('academic_sessions')],
        ]);

        $tid = $this->tid();
        $session = $data['session_id']
            ? \App\Models\AcademicSession::find($data['session_id'])
            : \App\Models\AcademicSession::where('is_current', true)->first();

        StudentSubjectSelection::where('tenant_id', $tid)
            ->where('student_id', $student->id)
            ->where('subject_id', $data['subject_id'])
            ->where('selection_type', 'elective')
            ->when($session, fn($q) => $q->where('session_id', $session->id))
            ->delete();

        return back()->with('success', 'Elective subject removed.');
    }

    // ═══════════════════════════════════════════════════════════════════
    // TEACHER SUBJECT ALLOCATION (class-arm level)
    // ═══════════════════════════════════════════════════════════════════

    public function armTeacherSubjects(ClassArm $arm)
    {
        $arm->load(['classLevel', 'academicTrack']);

        // Only show subjects that are valid for this arm's level + track
        $eligibleRules = ClassLevelSubject::where('tenant_id', $this->tid())
            ->where('class_level_id', $arm->class_level_id)
            ->where('is_active', true)
            ->where('subject_status', '!=', 'not_offered')
            ->where(function ($q) use ($arm) {
                $q->whereNull('academic_track_id');
                if ($arm->academic_track_id) {
                    $q->orWhere('academic_track_id', $arm->academic_track_id);
                }
            })
            ->with('subject')
            ->get();

        // Current teacher assignments for this arm
        $assignments = ClassArmSubject::where('tenant_id', $this->tid())
            ->where('class_arm_id', $arm->id)
            ->with(['subject', 'teacher'])
            ->get()
            ->keyBy('subject_id');

        $teachers = \App\Models\User::where('tenant_id', $this->tid())
            ->whereIn('role', array_merge(
                \App\Models\User::teachingRoleNames(),
                ['vice_principal', 'principal']
            ))
            ->orderBy('name')
            ->get();

        $sessions = \App\Models\AcademicSession::where('tenant_id', $this->tid())->latest()->limit(5)->get();

        return view('curriculum.arm-teacher-subjects', compact(
            'arm', 'eligibleRules', 'assignments', 'teachers', 'sessions'
        ));
    }

    public function setArmTeacher(Request $request, ClassArm $arm)
    {
        $data = $request->validate([
            'subject_id' => ['required', $this->tenantExists('subjects')],
            'teacher_id' => ['nullable', $this->tenantTeacherRule()],
            'session_id' => ['nullable', $this->tenantExists('academic_sessions')],
            'term_id'    => ['nullable', $this->tenantExists('terms')],
            'is_active'  => ['boolean'],
        ]);

        // Validate: subject must be eligible for this arm's level + track
        $eligible = ClassLevelSubject::where('tenant_id', $this->tid())
            ->where('class_level_id', $arm->class_level_id)
            ->where('subject_id', $data['subject_id'])
            ->where('is_active', true)
            ->where('subject_status', '!=', 'not_offered')
            ->where(function ($q) use ($arm) {
                $q->whereNull('academic_track_id');
                if ($arm->academic_track_id) {
                    $q->orWhere('academic_track_id', $arm->academic_track_id);
                }
            })->exists();

        if (!$eligible) {
            return back()->withErrors(['error' => 'This subject is not offered for this class arm\'s curriculum.']);
        }

        ClassArmSubject::updateOrCreate(
            [
                'tenant_id'   => $this->tid(),
                'class_arm_id'=> $arm->id,
                'subject_id'  => $data['subject_id'],
            ],
            [
                'teacher_id' => $data['teacher_id'] ?? null,
                'session_id' => $data['session_id'] ?? null,
                'term_id'    => $data['term_id'] ?? null,
                'is_active'  => $data['is_active'] ?? true,
            ]
        );

        $subject = Subject::find($data['subject_id']);
        $teacher = $data['teacher_id'] ? \App\Models\User::find($data['teacher_id'])?->name : 'unassigned';
        return back()->with('success', "'{$subject->name}' allocated to {$teacher} in {$arm->full_name}.");
    }

    public function removeArmTeacher(Request $request, ClassArm $arm)
    {
        $data = $request->validate(['subject_id' => ['required', $this->tenantExists('subjects')]]);

        ClassArmSubject::where('tenant_id', $this->tid())
            ->where('class_arm_id', $arm->id)
            ->where('subject_id', $data['subject_id'])
            ->delete();

        return back()->with('success', 'Teacher assignment removed.');
    }

    // ═══════════════════════════════════════════════════════════════════
    // MIGRATION UTILITY: Backfill ClassLevelSubjects from existing ClassArmSubjects
    // ═══════════════════════════════════════════════════════════════════

    public function backfillFromClassArmSubjects()
    {
        $tid = $this->tid();

        // Get distinct class_level + subject pairs from class_arm_subjects
        $pairs = DB::table('class_arm_subjects')
            ->join('class_arms', 'class_arms.id', '=', 'class_arm_subjects.class_arm_id')
            ->where('class_arm_subjects.tenant_id', $tid)
            ->select('class_arms.class_level_id', 'class_arm_subjects.subject_id')
            ->distinct()
            ->get();

        $inserted = 0;
        foreach ($pairs as $pair) {
            $exists = ClassLevelSubject::where('tenant_id', $tid)
                ->where('class_level_id', $pair->class_level_id)
                ->where('subject_id', $pair->subject_id)
                ->whereNull('academic_track_id')
                ->exists();

            if (!$exists) {
                ClassLevelSubject::create([
                    'tenant_id'         => $tid,
                    'class_level_id'    => $pair->class_level_id,
                    'academic_track_id' => null,
                    'subject_id'        => $pair->subject_id,
                    'subject_status'    => 'compulsory',
                    'is_active'         => true,
                ]);
                $inserted++;
            }
        }

        return back()->with('success', "Backfill complete. {$inserted} class-level subject rules created from existing class arm subjects.");
    }
}
