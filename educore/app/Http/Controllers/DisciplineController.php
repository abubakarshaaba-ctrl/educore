<?php

namespace App\Http\Controllers;

use App\Models\DisciplineRecord;
use App\Models\Student;
use Illuminate\Http\Request;

class DisciplineController extends Controller
{
    public function index(Request $request)
    {
        $records = DisciplineRecord::with(['student', 'recorder'])
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->student_id, fn ($q) => $q->where('student_id', $request->student_id))
            ->latest('occurred_at')
            ->paginate(25)
            ->withQueryString();

        $students = Student::where('status', Student::STATUS_ACTIVE)->orderBy('first_name')->get();

        return view('discipline.index', compact('records', 'students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'   => ['required', 'exists:students,id'],
            'type'         => ['required', 'in:merit,demerit,incident,suspension'],
            'category'     => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string'],
            'points'       => ['nullable', 'integer'],
            'occurred_at'  => ['required', 'date'],
            'suspension_start' => ['nullable', 'date', 'required_if:type,suspension'],
            'suspension_end'   => ['nullable', 'date', 'after_or_equal:suspension_start'],
            'action_taken' => ['nullable', 'string'],
        ]);

        $data['recorded_by'] = auth()->id();
        $data['points'] = $data['points'] ?? ($data['type'] === 'merit' ? 1 : ($data['type'] === 'demerit' ? -1 : 0));

        DisciplineRecord::create($data);

        return back()->with('success', 'Discipline record saved.');
    }

    public function resolve(DisciplineRecord $record)
    {
        $record->update(['status' => 'resolved']);
        return back()->with('success', 'Marked as resolved.');
    }

    public function destroy(DisciplineRecord $record)
    {
        $record->delete();
        return back()->with('success', 'Record deleted.');
    }
}
