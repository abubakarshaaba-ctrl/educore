<?php

namespace App\Http\Controllers;

use App\Models\ClassCoverageAssignment;
use App\Models\TimetablePeriod;
use App\Models\User;
use Illuminate\Http\Request;

class ClassCoverageController extends Controller
{
    public function index(Request $request)
    {
        $assignments = ClassCoverageAssignment::with(['classArm.classLevel', 'subject', 'absentTeacher', 'coveringTeacher'])
            ->when($request->date, fn ($q) => $q->where('coverage_date', $request->date))
            ->latest('coverage_date')
            ->paginate(25)
            ->withQueryString();

        $staff = User::activeStaff(auth()->user()->tenant_id)->orderBy('name')->get();

        return view('coverage.index', compact('assignments', 'staff'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'absent_teacher_id'   => ['required', 'exists:users,id', 'different:covering_teacher_id'],
            'covering_teacher_id' => ['required', 'exists:users,id'],
            'coverage_date'       => ['required', 'date'],
            'notes'               => ['nullable', 'string'],
        ]);

        // Pull the absent teacher's timetable for that day so the admin can
        // see/assign per period, but for MVP we record one coverage entry
        // covering all of that teacher's periods that day.
        $dayOfWeek = ucfirst(strtolower(\Carbon\Carbon::parse($data['coverage_date'])->format('l')));
        $periods = TimetablePeriod::where('teacher_id', $data['absent_teacher_id'])
            ->where('day_of_week', $dayOfWeek)
            ->get();

        if ($periods->isEmpty()) {
            ClassCoverageAssignment::create([
                'absent_teacher_id'   => $data['absent_teacher_id'],
                'covering_teacher_id' => $data['covering_teacher_id'],
                'coverage_date'       => $data['coverage_date'],
                'notes'               => $data['notes'] ?? null,
                'assigned_by'         => auth()->id(),
                'status'              => 'scheduled',
            ]);
        } else {
            foreach ($periods as $period) {
                ClassCoverageAssignment::create([
                    'timetable_period_id' => $period->id,
                    'class_arm_id'        => $period->class_arm_id,
                    'subject_id'          => $period->subject_id,
                    'absent_teacher_id'   => $data['absent_teacher_id'],
                    'covering_teacher_id' => $data['covering_teacher_id'],
                    'coverage_date'       => $data['coverage_date'],
                    'notes'               => $data['notes'] ?? null,
                    'assigned_by'         => auth()->id(),
                    'status'              => 'scheduled',
                ]);
            }
        }

        return back()->with('success', 'Class coverage assigned for ' . $periods->count() . ' period(s).');
    }

    public function complete(ClassCoverageAssignment $assignment)
    {
        $assignment->update(['status' => 'completed']);
        return back()->with('success', 'Marked as completed.');
    }

    public function cancel(ClassCoverageAssignment $assignment)
    {
        $assignment->update(['status' => 'cancelled']);
        return back()->with('success', 'Coverage cancelled.');
    }
}
