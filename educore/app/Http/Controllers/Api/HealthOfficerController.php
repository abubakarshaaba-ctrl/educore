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
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
        $search = trim((string) ($data['search'] ?? ''));
        $perPage = (int) ($data['per_page'] ?? 40);

        $activeStudentQuery = Student::query()
            ->where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE);
        $studentCount = (clone $activeStudentQuery)->count();
        $records = StudentHealthRecord::query()->where('tenant_id', $tenantId);

        $students = (clone $activeStudentQuery)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($nested) use ($like) {
                    $nested->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('other_names', 'like', $like)
                        ->orWhere('admission_number', 'like', $like);
                });
            })
            ->with(['currentClassArm.classLevel:id,name', 'healthRecord'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage);

        return response()->json([
            'capabilities' => [
                'manage' => $user->canManage('health'),
            ],
            'metrics' => [
                'students' => $studentCount,
                'records' => (clone $records)->count(),
                'allergy_alerts' => (clone $records)->whereNotNull('allergies')->where('allergies', '!=', '')->count(),
                'medication_alerts' => (clone $records)->whereNotNull('current_medications')->where('current_medications', '!=', '')->count(),
            ],
            'students' => collect($students->items())->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => trim("{$student->first_name} {$student->last_name}"),
                'admission_number' => $student->admission_number,
                'class' => $student->currentClassArm?->full_name ?? 'Unassigned',
                'has_record' => $student->healthRecord !== null,
                'allergy_alert' => filled($student->healthRecord?->allergies),
                'medication_alert' => filled($student->healthRecord?->current_medications),
            ])->values(),
            'selected' => [
                'search' => $search,
            ],
            'meta' => [
                'page' => $students->currentPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'last_page' => $students->lastPage(),
                'has_more' => $students->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, Student $student)
    {
        $user = $this->guard($request);
        abort_unless((int) $student->tenant_id === (int) $user->tenant_id, 404);
        $student->load('currentClassArm.classLevel:id,name');
        $record = StudentHealthRecord::where('tenant_id', $user->tenant_id)->where('student_id', $student->id)->first();

        return response()->json([
            'capabilities' => [
                'manage' => $user->canManage('health'),
            ],
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
        $user = $this->guard($request, manage: true);
        abort_unless((int) $student->tenant_id === (int) $user->tenant_id, 404);
        $rules = collect($this->fields())->mapWithKeys(fn ($field) => [$field => ['nullable', 'string', 'max:2000']])->all();
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

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School health access required.');
        abort_unless($user->tenant_id, 403, 'School health access required.');
        abort_unless(
            $manage ? $user->canManage('health') : $user->canAccessModule('health'),
            403,
            $manage ? 'Health record management permission required.' : 'Health records access required.'
        );

        return $user;
    }
}
