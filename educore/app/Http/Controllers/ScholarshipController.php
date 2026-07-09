<?php

namespace App\Http\Controllers;

use App\Models\Scholarship;
use App\Models\Student;
use Illuminate\Http\Request;

class ScholarshipController extends Controller
{
    public function index()
    {
        $scholarships = Scholarship::with('student')->latest()->paginate(25);
        $students = Student::where('status', Student::STATUS_ACTIVE)->orderBy('first_name')->get();

        return view('scholarships.index', compact('scholarships', 'students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'name'       => ['required', 'string', 'max:150'],
            'type'       => ['required', 'in:percentage,fixed_amount,full_waiver'],
            'value'      => ['required_unless:type,full_waiver', 'nullable', 'numeric', 'min:0'],
            'reason'     => ['nullable', 'string'],
            'starts_at'  => ['nullable', 'date'],
            'ends_at'    => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data['value'] = $data['type'] === 'full_waiver' ? 100 : ($data['value'] ?? 0);
        $data['approved_by'] = auth()->id();
        $data['status'] = 'active';

        Scholarship::create($data);

        return back()->with('success', 'Scholarship recorded.');
    }

    public function revoke(Scholarship $scholarship)
    {
        $scholarship->update(['status' => 'revoked']);
        return back()->with('success', 'Scholarship revoked.');
    }
}
