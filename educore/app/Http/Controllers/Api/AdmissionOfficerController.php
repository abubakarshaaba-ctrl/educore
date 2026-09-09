<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdmissionOfficerController extends Controller
{
    private const STATUSES = ['pending', 'shortlisted', 'admitted', 'rejected', 'withdrawn'];

    public function index(Request $request)
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $status = trim((string) $request->input('status', 'all'));
        $search = trim((string) $request->input('search', ''));

        $base = Admission::query()->where('tenant_id', $tenantId);
        $query = (clone $base)->with('applyingForClassLevel:id,name')->latest();
        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $like = "%{$search}%";
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('other_names', 'like', $like)
                    ->orWhere('application_number', 'like', $like)
                    ->orWhere('guardian_name', 'like', $like)
                    ->orWhere('guardian_phone', 'like', $like);
            });
        }

        $admissions = $query->limit(100)->get()->map(fn (Admission $item) => $this->payload($item));

        return response()->json([
            'contract_version' => 1,
            'module' => [
                'key' => 'admissions',
                'title' => 'Admissions',
                'description' => 'Applicant review and enrolment',
                'mobile_policy' => 'native_manage',
            ],
            'capabilities' => [
                'create' => true,
                'change_status' => true,
                'schedule_interview' => false,
                'record_interview' => false,
            ],
            'stats' => [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'shortlisted' => (clone $base)->where('status', 'shortlisted')->count(),
                'admitted' => (clone $base)->where('status', 'admitted')->count(),
                'rejected' => (clone $base)->where('status', 'rejected')->count(),
                'withdrawn' => (clone $base)->where('status', 'withdrawn')->count(),
            ],
            'status_options' => collect(self::STATUSES)->map(fn (string $key) => [
                'key' => $key,
                'label' => ucfirst($key),
            ])->values(),
            'class_levels' => ClassLevel::where('tenant_id', $tenantId)->orderBy('order_index')->get(['id', 'name']),
            'class_arms' => ClassArm::where('tenant_id', $tenantId)->with('classLevel:id,name')->get()->map(fn ($arm) => [
                'id' => $arm->id,
                'name' => trim(($arm->classLevel?->name ?? '').' '.$arm->name),
            ])->values(),
            'admissions' => $admissions,
            'selected' => [
                'status' => $status === '' ? 'all' : $status,
                'search' => $search,
            ],
            'meta' => [
                'page' => 1,
                'per_page' => 100,
                'total' => $admissions->count(),
                'last_page' => 1,
                'has_more' => false,
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'other_names' => ['nullable', 'string', 'max:80'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'in:male,female'],
            'applying_for_class_level_id' => ['nullable', 'integer', 'exists:class_levels,id'],
            'guardian_name' => ['required', 'string', 'max:160'],
            'guardian_phone' => ['required', 'string', 'max:40'],
            'guardian_email' => ['nullable', 'email'],
            'guardian_relationship' => ['required', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
        $data += [
            'tenant_id' => $user->tenant_id,
            'application_number' => 'APP-'.date('Y').'-'.strtoupper(Str::random(6)),
            'application_date' => today()->toDateString(),
            'status' => 'pending',
        ];
        $admission = Admission::create($data)->load('applyingForClassLevel:id,name');

        return response()->json([
            'message' => 'Admission application created.',
            'admission' => $this->payload($admission),
        ], 201);
    }

    public function updateStatus(Request $request, Admission $admission)
    {
        $user = $this->guard($request);
        abort_unless((int) $admission->tenant_id === (int) $user->tenant_id, 404);
        app(\App\Http\Controllers\AdmissionController::class)->updateStatus($request, $admission);
        $fresh = $admission->fresh('applyingForClassLevel:id,name');

        return response()->json([
            'message' => 'Application status updated.',
            'admission' => $this->payload($fresh),
        ]);
    }

    private function payload(Admission $item): array
    {
        return [
            'id' => $item->id,
            'application_number' => (string) $item->application_number,
            'name' => trim("{$item->first_name} {$item->other_names} {$item->last_name}"),
            'first_name' => (string) $item->first_name,
            'last_name' => (string) $item->last_name,
            'other_names' => $item->other_names,
            'gender' => $item->gender,
            'date_of_birth' => $item->date_of_birth,
            'class_level_id' => $item->applying_for_class_level_id,
            'class_level' => $item->applyingForClassLevel?->name,
            'guardian_name' => (string) $item->guardian_name,
            'guardian_phone' => (string) $item->guardian_phone,
            'guardian_email' => $item->guardian_email,
            'status' => (string) $item->status,
            'notes' => $item->notes,
            'application_date' => $item->application_date,
            'interview_date' => $item->interview_date,
            'interview_score' => $item->interview_score !== null ? (float) $item->interview_score : null,
            'offer_letter_sent' => (bool) ($item->offer_letter_sent ?? false),
            'enrolled_student_id' => $item->enrolled_as_student_id,
            'guardian_relationship' => $item->guardian_relationship,
            'address' => $item->address,
            'interview_notes' => $item->interview_notes,
            'decision_date' => $item->decision_date,
        ];
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        $access = User::ROLE_ACCESS[$user?->roleKey()] ?? [];
        abort_unless($user && $user->tenant_id && (in_array('*', $access, true) || in_array('admissions', $access, true)), 403, 'Admissions access required.');
        return $user;
    }
}
