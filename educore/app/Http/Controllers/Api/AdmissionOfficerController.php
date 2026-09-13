<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdmissionOfficerController extends Controller
{
    private const STATUSES = ['pending', 'shortlisted', 'admitted', 'rejected', 'withdrawn'];

    public function index(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'status' => ['nullable', Rule::in(array_merge(['all'], self::STATUSES))],
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $status = $data['status'] ?? 'all';
        $search = trim((string) ($data['search'] ?? ''));
        $perPage = (int) ($data['per_page'] ?? 30);

        $query = Admission::with('applyingForClassLevel:id,name')
            ->where('tenant_id', $user->tenant_id)
            ->latest('id');

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('other_names', 'like', $like)
                    ->orWhere('application_number', 'like', $like)
                    ->orWhere('guardian_name', 'like', $like)
                    ->orWhere('guardian_phone', 'like', $like);
            });
        }

        $page = $query->paginate($perPage);
        $base = Admission::where('tenant_id', $user->tenant_id);

        return response()->json([
            'contract_version' => 2,
            'module' => [
                'key' => 'admissions',
                'title' => 'Admissions',
                'description' => 'Review applicants and convert successful applications into enrolled students',
                'mobile_policy' => 'native',
            ],
            'capabilities' => [
                'create' => true,
                'change_status' => true,
                'schedule_interview' => true,
                'record_interview' => true,
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
            'class_levels' => ClassLevel::where('tenant_id', $user->tenant_id)
                ->orderBy('order_index')->orderBy('name')->get(['id', 'name']),
            'class_arms' => ClassArm::where('tenant_id', $user->tenant_id)
                ->with('classLevel:id,name')->orderBy('class_level_id')->orderBy('name')->get()
                ->map(fn (ClassArm $arm) => [
                    'id' => $arm->id,
                    'name' => trim(($arm->classLevel?->name ?? '').' '.$arm->name),
                ])->values(),
            'admissions' => collect($page->items())->map(fn (Admission $item) => $this->payload($item))->values(),
            'selected' => ['status' => $status, 'search' => $search],
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
                'has_more' => $page->hasMorePages(),
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function show(Request $request, Admission $admission)
    {
        $user = $this->guard($request);
        $this->assertTenant($admission, $user);
        $admission->load('applyingForClassLevel:id,name');

        return response()->json(['admission' => $this->payload($admission)]);
    }

    public function store(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'other_names' => ['nullable', 'string', 'max:80'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'applying_for_class_level_id' => [
                'nullable', 'integer',
                Rule::exists('class_levels', 'id')->where('tenant_id', $user->tenant_id),
            ],
            'guardian_name' => ['required', 'string', 'max:160'],
            'guardian_phone' => ['required', 'string', 'max:40'],
            'guardian_email' => ['nullable', 'email', 'max:160'],
            'guardian_relationship' => ['required', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:4000'],
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
        $this->assertTenant($admission, $user);
        $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:4000'],
            'class_arm_id' => [
                'nullable', 'integer',
                Rule::exists('class_arms', 'id')->where('tenant_id', $user->tenant_id),
            ],
        ]);

        // Keep the mature web admission workflow authoritative for enrolment,
        // plan limits and guardian notifications, then translate to native JSON.
        app(\App\Http\Controllers\AdmissionController::class)->updateStatus($request, $admission);
        $fresh = $admission->fresh(['applyingForClassLevel:id,name']);

        return response()->json([
            'message' => 'Application status updated to '.ucfirst($fresh->status).'.',
            'admission' => $this->payload($fresh),
        ]);
    }

    public function scheduleInterview(Request $request, Admission $admission)
    {
        $user = $this->guard($request);
        $this->assertTenant($admission, $user);
        $request->validate([
            'interview_date' => ['required', 'date', 'after_or_equal:today'],
            'interview_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        app(\App\Http\Controllers\AdmissionController::class)->scheduleInterview($request, $admission);
        $fresh = $admission->fresh(['applyingForClassLevel:id,name']);

        return response()->json([
            'message' => 'Interview scheduled and guardian notified.',
            'admission' => $this->payload($fresh),
        ]);
    }

    public function recordInterview(Request $request, Admission $admission)
    {
        $user = $this->guard($request);
        $this->assertTenant($admission, $user);
        $request->validate([
            'interview_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        app(\App\Http\Controllers\AdmissionController::class)->recordInterview($request, $admission);
        $fresh = $admission->fresh(['applyingForClassLevel:id,name']);

        return response()->json([
            'message' => 'Interview score recorded.',
            'admission' => $this->payload($fresh),
        ]);
    }

    public function sendOffer(Request $request, Admission $admission)
    {
        $user = $this->guard($request);
        $this->assertTenant($admission, $user);
        abort_unless($admission->status === 'admitted', 422, 'Applicant must be admitted before sending an offer letter.');

        app(\App\Http\Controllers\AdmissionController::class)->sendOffer($admission);
        $fresh = $admission->fresh(['applyingForClassLevel:id,name']);

        return response()->json([
            'message' => 'Admission offer sent.',
            'admission' => $this->payload($fresh),
        ]);
    }

    private function payload(Admission $item): array
    {
        return [
            'id' => (int) $item->id,
            'application_number' => (string) $item->application_number,
            'name' => trim(implode(' ', array_filter([$item->first_name, $item->other_names, $item->last_name]))),
            'first_name' => (string) $item->first_name,
            'last_name' => (string) $item->last_name,
            'other_names' => $item->other_names,
            'gender' => $item->gender,
            'date_of_birth' => optional($item->date_of_birth)->toDateString() ?? (is_string($item->date_of_birth) ? $item->date_of_birth : null),
            'class_level_id' => $item->applying_for_class_level_id,
            'class_level' => $item->applyingForClassLevel?->name,
            'guardian_name' => (string) $item->guardian_name,
            'guardian_phone' => (string) $item->guardian_phone,
            'guardian_email' => $item->guardian_email,
            'guardian_relationship' => $item->guardian_relationship,
            'address' => $item->address,
            'status' => (string) $item->status,
            'notes' => $item->notes,
            'application_date' => optional($item->application_date)->toDateString() ?? (is_string($item->application_date) ? $item->application_date : null),
            'interview_date' => optional($item->interview_date)->toDateString() ?? (is_string($item->interview_date) ? $item->interview_date : null),
            'interview_score' => $item->interview_score !== null ? (float) $item->interview_score : null,
            'interview_notes' => $item->interview_notes,
            'offer_letter_sent' => (bool) ($item->offer_letter_sent ?? false),
            'enrolled_student_id' => $item->enrolled_as_student_id ? (int) $item->enrolled_as_student_id : null,
            'decision_date' => optional($item->decision_date)->toDateString() ?? (is_string($item->decision_date) ? $item->decision_date : null),
        ];
    }

    private function assertTenant(Admission $admission, User $user): void
    {
        abort_unless((int) $admission->tenant_id === (int) $user->tenant_id, 404, 'Admission application not found.');
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        $access = User::ROLE_ACCESS[$user?->roleKey()] ?? [];
        abort_unless(
            $user && $user->tenant_id && (in_array('*', $access, true) || in_array('admissions', $access, true)),
            403,
            'Admissions access required.'
        );
        return $user;
    }
}
