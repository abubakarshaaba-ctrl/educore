<?php

namespace App\Http\Controllers;

use App\Models\AlumniProfile;
use App\Models\Student;
use Illuminate\Http\Request;

class AlumniController extends Controller
{
    public function index(Request $request)
    {
        $alumni = Student::whereIn('status', [Student::STATUS_GRADUATED, Student::STATUS_WITHDRAWN])
            ->with('alumniProfile')
            ->when($request->q, fn ($q) => $q->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$request->q}%")
                ->orWhere('last_name', 'like', "%{$request->q}%")
                ->orWhere('admission_number', 'like', "%{$request->q}%")))
            ->orderByDesc('graduation_date')
            ->paginate(25)
            ->withQueryString();

        return view('alumni.index', compact('alumni'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'graduation_year'     => ['nullable', 'string', 'max:9'],
            'further_institution' => ['nullable', 'string', 'max:150'],
            'occupation'          => ['nullable', 'string', 'max:120'],
            'employer'            => ['nullable', 'string', 'max:150'],
            'contact_email'       => ['nullable', 'email', 'max:150'],
            'contact_phone'       => ['nullable', 'string', 'max:30'],
            'notes'               => ['nullable', 'string'],
        ]);

        AlumniProfile::updateOrCreate(['student_id' => $student->id], $data);

        return back()->with('success', 'Alumni profile updated.');
    }
}
