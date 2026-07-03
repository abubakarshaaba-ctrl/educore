<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentHealthRecord;
use Illuminate\Http\Request;

class HealthRecordController extends Controller
{
    public function index()
    {
        $students = Student::with('healthRecord')->orderBy('last_name')->paginate(25);
        return view('health.index', compact('students'));
    }

    public function show(Student $student)
    {
        $record = StudentHealthRecord::firstOrNew([
            'tenant_id'  => auth()->user()->tenant_id,
            'student_id' => $student->id,
        ]);
        return view('health.show', compact('student', 'record'));
    }

    public function upsert(Request $request, Student $student)
    {
        $data = $request->validate([
            'blood_group'                   => ['nullable','string','max:5'],
            'genotype'                      => ['nullable','string','max:5'],
            'allergies'                     => ['nullable','string'],
            'chronic_conditions'            => ['nullable','string'],
            'current_medications'           => ['nullable','string'],
            'disability'                    => ['nullable','string'],
            'emergency_contact_name'        => ['nullable','string'],
            'emergency_contact_phone'       => ['nullable','string'],
            'emergency_contact_relationship'=> ['nullable','string'],
            'doctor_name'                   => ['nullable','string'],
            'doctor_phone'                  => ['nullable','string'],
            'notes'                         => ['nullable','string'],
        ]);
        StudentHealthRecord::updateOrCreate(
            ['tenant_id' => auth()->user()->tenant_id, 'student_id' => $student->id],
            $data
        );
        return back()->with('success', 'Health record updated.');
    }
}
