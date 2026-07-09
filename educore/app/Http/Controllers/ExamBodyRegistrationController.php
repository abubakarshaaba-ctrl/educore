<?php

namespace App\Http\Controllers;

use App\Models\ExamBodyRegistration;
use App\Models\Student;
use Illuminate\Http\Request;

class ExamBodyRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $registrations = ExamBodyRegistration::with('student')
            ->when($request->exam_body, fn ($q) => $q->where('exam_body', $request->exam_body))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $students = Student::where('status', Student::STATUS_ACTIVE)->orderBy('first_name')->get();

        return view('exam-bodies.index', compact('registrations', 'students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'          => ['required', 'exists:students,id'],
            'exam_body'           => ['required', 'in:WAEC,NECO,NABTEB,JAMB'],
            'exam_year'           => ['required', 'string', 'max:9'],
            'registration_number' => ['nullable', 'string', 'max:60'],
            'subjects'            => ['nullable', 'string'],
        ]);

        $data['subjects'] = $data['subjects'] ?? null;
        if ($data['subjects']) {
            $data['subjects'] = array_map('trim', explode(',', $data['subjects']));
        }
        $data['status'] = ($data['registration_number'] ?? null) ? 'registered' : 'pending';
        $data['registered_by'] = auth()->id();

        ExamBodyRegistration::create($data);

        return back()->with('success', 'Candidate registration recorded.');
    }

    public function update(Request $request, ExamBodyRegistration $registration)
    {
        $data = $request->validate([
            'registration_number' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'in:pending,registered,completed'],
        ]);

        $registration->update($data);

        return back()->with('success', 'Registration updated.');
    }

    public function destroy(ExamBodyRegistration $registration)
    {
        $registration->delete();
        return back()->with('success', 'Registration removed.');
    }
}
