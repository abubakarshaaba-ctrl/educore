<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentHealthRecord;
use App\Models\User;
use Illuminate\Http\Request;

class HealthOfficerController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $this->guard($request);
        $tenantId = $user->tenant_id;
        $students = Student::where('tenant_id', $tenantId)->where('status', Student::STATUS_ACTIVE)
            ->with(['currentClassArm.classLevel:id,name', 'healthRecord'])->orderBy('last_name')->limit(150)->get();
        $records = StudentHealthRecord::where('tenant_id', $tenantId);

        return response()->json([
            'metrics' => [
                'students' => $students->count(),
                'records' => (clone $records)->count(),
                'allergy_alerts' => (clone $records)->whereNotNull('allergies')->where('allergies', '!=', '')->count(),
                'medication_alerts' => (clone $records)->whereNotNull('current_medications')->where('current_medications', '!=', '')->count(),
            ],
            'students' => $students->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => trim("{$student->first_name} {$student->last_name}"),
                'admission_number' => $student->admission_number,
                'class' => $student->currentClassArm?->full_name ?? 'Unassigned',
                'has_record' => $student->healthRecord !== null,
                'allergy_alert' => filled($student->healthRecord?->allergies),
                'medication_alert' => filled($student->healthRecord?->current_medications),
            ]),
        ]);
    }

    public function show(Request $request, Student $student)
    {
        $user = $this->guard($request);
        abort_unless((int) $student->tenant_id === (int) $user->tenant_id, 404);
        $student->load('currentClassArm.classLevel:id,name');
        $record = StudentHealthRecord::where('tenant_id', $user->tenant_id)->where('student_id', $student->id)->first();
        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => trim("{$student->first_name} {$student->last_name}"),
                'admission_number' => $student->admission_number,
                'class' => $student->currentClassArm?->full_name ?? 'Unassigned',
            ],
            'record' => $record?->only($this->fields()) ?? [],
        ]);
    }

    public function upsert(Request $request, Student $student)
    {
        $user = $this->guard($request);
        abort_unless((int) $student->tenant_id === (int) $user->tenant_id, 404);
        $rules = collect($this->fields())->mapWithKeys(fn ($field) => [$field => ['nullable', 'string', 'max:2000']])->all();
        foreach (['blood_group', 'genotype'] as $short) $rules[$short] = ['nullable', 'string', 'max:5'];
        foreach (['emergency_contact_phone', 'doctor_phone'] as $phone) $rules[$phone] = ['nullable', 'string', 'max:30'];
        $data = $request->validate($rules);
        $record = StudentHealthRecord::updateOrCreate(
            ['tenant_id' => $user->tenant_id, 'student_id' => $student->id],
            $data + ['tenant_id' => $user->tenant_id]
        );
        return response()->json(['message' => 'Health record updated securely.', 'record' => $record->only($this->fields())]);
    }

    private function fields(): array
    {
        return ['blood_group', 'genotype', 'allergies', 'chronic_conditions', 'current_medications', 'disability', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship', 'doctor_name', 'doctor_phone', 'notes'];
    }

    private function guard(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user && in_array($user->roleKey(), ['health_officer', 'admin'], true), 403, 'Health Officer access required.');
        return $user;
    }
}
