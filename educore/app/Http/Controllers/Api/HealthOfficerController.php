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
        $search = trim((string) $request->query('search', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 40)));

        $studentQuery = Student::where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_number', 'like', "%{$search}%");
                });
            });

        $studentCount = Student::where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
            ->count();
        $records = StudentHealthRecord::where('tenant_id', $tenantId);
        $studentPage = $studentQuery
            ->with(['currentClassArm.classLevel:id,name', 'healthRecord'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage);

        $students = collect($studentPage->items())->map(fn (Student $student) => [
            'id' => $student->id,
            'name' => trim("{$student->first_name} {$student->last_name}"),
            'admission_number' => $student->admission_number,
            'class' => $student->currentClassArm?->full_name ?? 'Unassigned',
            'has_record' => $student->healthRecord !== null,
            'allergy_alert' => filled($student->healthRecord?->allergies),
            'medication_alert' => filled($student->healthRecord?->current_medications),
        ])->values();

        return response()->json([
            'capabilities' => ['manage' => $this->canManage($user)],
            'metrics' => [
                'students' => $studentCount,
                'records' => (clone $records)->count(),
                'allergy_alerts' => (clone $records)->whereNotNull('allergies')->where('allergies', '!=', '')->count(),
                'medication_alerts' => (clone $records)->whereNotNull('current_medications')->where('current_medications', '!=', '')->count(),
            ],
            'students' => $students,
            'selected' => ['search' => $search],
            'meta' => [
                'page' => $studentPage->currentPage(),
                'per_page' => $studentPage->perPage(),
                'total' => $studentPage->total(),
                'last_page' => $studentPage->lastPage(),
                'has_more' => $studentPage->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, Student $student)
    {
        $user = $this->guard($request);
        abort_unless((int) $student->tenant_id === (int) $user->tenant_id, 404);
        $student->load('currentClassArm.classLevel:id,name');
        $record = StudentHealthRecord::where('tenant_id', $user->tenant_id)
            ->where('student_id', $student->id)
            ->first();

        return response()->json([
            'capabilities' => ['manage' => $this->canManage($user)],
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
        abort_unless($this->canManage($user), 403, 'Health record management access required.');
        abort_unless((int) $student->tenant_id === (int) $user->tenant_id, 404);

        $rules = collect($this->fields())
            ->mapWithKeys(fn ($field) => [$field => ['nullable', 'string', 'max:2000']])
            ->all();
        foreach (['blood_group', 'genotype'] as $short) {
            $rules[$short] = ['nullable', 'string', 'max:5'];
        }
        foreach (['emergency_contact_phone', 'doctor_phone'] as $phone) {
            $rules[$phone] = ['nullable', 'string', 'max:30'];
        }

        $data = $request->validate($rules);
        $record = StudentHealthRecord::updateOrCreate(
            ['tenant_id' => $user->tenant_id, 'student_id' => $student->id],
            $data + ['tenant_id' => $user->tenant_id]
        );

        return response()->json([
            'message' => 'Health record updated securely.',
            'record' => $record->only($this->fields()),
        ]);
    }

    private function fields(): array
    {
        return [
            'blood_group',
            'genotype',
            'allergies',
            'chronic_conditions',
            'current_medications',
            'disability',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relationship',
            'doctor_name',
            'doctor_phone',
            'notes',
        ];
    }

    private function guard(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user && in_array($user->roleKey(), ['health_officer', 'admin'], true), 403, 'Health Officer access required.');

        return $user;
    }

    private function canManage(User $user): bool
    {
        return in_array($user->roleKey(), ['health_officer', 'admin'], true);
    }
}
