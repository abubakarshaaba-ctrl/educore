<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Guardian;
use App\Models\User;
use App\Services\Admissions\AdmissionStatusService;
use App\Services\GuardianNotifier;
use App\Services\TenantUrlGenerator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdmissionOfficerController extends Controller
{
    private const STATUSES = ['pending', 'shortlisted', 'admitted', 'rejected', 'withdrawn'];

    public function index(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(array_merge(['all'], self::STATUSES))],
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $status = $validated['status'] ?? 'all';
        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 30);

        $base = Admission::query()->where('tenant_id', $tenantId);
        $query = (clone $base)
            ->with('applyingForClassLevel:id,name')
            ->when($status !== 'all', fn ($builder) => $builder->where('status', $status))
            ->when($search !== '', function ($builder) use ($search): void {
                $like = '%'.$search.'%';
                $builder->where(function ($nested) use ($like): void {
                    $nested->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('other_names', 'like', $like)
                        ->orWhere('application_number', 'like', $like)
                        ->orWhere('guardian_name', 'like', $like)
                        ->orWhere('guardian_phone', 'like', $like);
                });
            })
            ->latest('application_date')
            ->latest('id');

        $page = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'contract_version' => 1,
            'module' => [
                'key' => 'admissions',
                'title' => 'Admissions',
                'description' => 'Review applications, make decisions and coordinate applicant interviews',
                'mobile_policy' => 'native_manage',
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
            'class_levels' => ClassLevel::query()
                ->where('tenant_id', $tenantId)
                ->orderBy('order_index')
                ->get(['id', 'name']),
            'class_arms' => ClassArm::query()
                ->where('tenant_id', $tenantId)
                ->with('classLevel:id,name')
                ->get()
                ->sortBy('full_name')
                ->values()
                ->map(fn (ClassArm $arm) => [
                    'id' => $arm->id,
                    'name' => $arm->full_name,
                ]),
            'admissions' => collect($page->items())
                ->map(fn (Admission $item) => $this->payload($item))
                ->values(),
            'selected' => [
                'status' => $status,
                'search' => $search,
            ],
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

    public function show(Request $request, Admission $admission): JsonResponse
    {
        $user = $this->guard($request);
        $this->assertTenant($user, $admission);
        $admission->load(['applyingForClassLevel:id,name', 'enrolledStudent:id,admission_number,first_name,last_name']);

        return response()->json([
            'contract_version' => 1,
            'admission' => $this->payload($admission, includeDetails: true),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'other_names' => ['nullable', 'string', 'max:80'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'applying_for_class_level_id' => [
                'nullable',
                'integer',
                Rule::exists('class_levels', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'guardian_name' => ['required', 'string', 'max:160'],
            'guardian_phone' => ['required', 'string', 'max:40'],
            'guardian_email' => ['nullable', 'email', 'max:160'],
            'guardian_relationship' => ['required', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data += [
            'tenant_id' => $tenantId,
            'application_number' => 'APP-'.date('Y').'-'.strtoupper(Str::random(6)),
            'application_date' => today()->toDateString(),
            'status' => 'pending',
        ];
        $admission = Admission::create($data)->load('applyingForClassLevel:id,name');

        return response()->json([
            'message' => 'Admission application created.',
            'admission' => $this->payload($admission, includeDetails: true),
        ], 201);
    }

    public function updateStatus(
        Request $request,
        Admission $admission,
        AdmissionStatusService $workflow,
    ): JsonResponse {
        $user = $this->guard($request);
        $this->assertTenant($user, $admission);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'class_arm_id' => [
                'nullable',
                'integer',
                Rule::exists('class_arms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ]);

        $updated = $workflow->transition($user, $admission, $data)
            ->load(['applyingForClassLevel:id,name', 'enrolledStudent:id,admission_number,first_name,last_name']);

        return response()->json([
            'message' => 'Application status updated.',
            'admission' => $this->payload($updated, includeDetails: true),
        ]);
    }

    public function scheduleInterview(Request $request, Admission $admission): JsonResponse
    {
        $user = $this->guard($request);
        $this->assertTenant($user, $admission);
        $data = $request->validate([
            'interview_date' => ['required', 'date', 'after_or_equal:today'],
            'interview_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $admission->update($data);
        $this->notifyInterview($user, $admission->fresh());

        return response()->json([
            'message' => 'Interview scheduled and guardian notification queued.',
            'admission' => $this->payload($admission->fresh(['applyingForClassLevel:id,name']), includeDetails: true),
        ]);
    }

    public function recordInterview(Request $request, Admission $admission): JsonResponse
    {
        $user = $this->guard($request);
        $this->assertTenant($user, $admission);
        $data = $request->validate([
            'interview_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $admission->update($data);

        return response()->json([
            'message' => 'Interview score recorded.',
            'admission' => $this->payload($admission->fresh(['applyingForClassLevel:id,name']), includeDetails: true),
        ]);
    }

    private function guard(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless(
            $user && $user->tenant_id && ! $user->isStudent() && ! $user->isParent() && ! $user->isSuperAdmin()
                && $user->canAccessModule('admissions'),
            403,
            'Admissions access required.'
        );

        return $user;
    }

    private function assertTenant(User $user, Admission $admission): void
    {
        abort_unless((int) $admission->tenant_id === (int) $user->tenant_id, 404);
    }

    private function payload(Admission $item, bool $includeDetails = false): array
    {
        $payload = [
            'id' => $item->id,
            'application_number' => $item->application_number,
            'name' => trim("{$item->first_name} {$item->other_names} {$item->last_name}"),
            'first_name' => $item->first_name,
            'last_name' => $item->last_name,
            'other_names' => $item->other_names,
            'gender' => $item->gender,
            'date_of_birth' => $item->date_of_birth,
            'class_level_id' => $item->applying_for_class_level_id,
            'class_level' => $item->applyingForClassLevel?->name,
            'guardian_name' => $item->guardian_name,
            'guardian_phone' => $item->guardian_phone,
            'guardian_email' => $item->guardian_email,
            'status' => $item->status,
            'notes' => $item->notes,
            'application_date' => $item->application_date,
            'interview_date' => $item->interview_date,
            'interview_score' => $item->interview_score !== null ? (float) $item->interview_score : null,
            'offer_letter_sent' => (bool) $item->offer_letter_sent,
            'enrolled_student_id' => $item->enrolled_as_student_id,
        ];

        if ($includeDetails) {
            $payload += [
                'guardian_relationship' => $item->guardian_relationship,
                'address' => $item->address,
                'interview_notes' => $item->interview_notes,
                'decision_date' => $item->decision_date,
                'offer_sent_at' => $item->offer_sent_at?->toIso8601String(),
                'enrolled_student' => $item->enrolledStudent ? [
                    'id' => $item->enrolledStudent->id,
                    'admission_number' => $item->enrolledStudent->admission_number,
                    'name' => $item->enrolledStudent->full_name,
                ] : null,
            ];
        }

        return $payload;
    }

    private function notifyInterview(User $user, Admission $admission): void
    {
        $tenant = $user->tenant;
        $name = trim($admission->first_name.' '.$admission->last_name);
        $dateLabel = Carbon::parse($admission->interview_date)->format('d M Y');
        $guardian = new Guardian([
            'first_name' => $admission->guardian_name,
            'email' => $admission->guardian_email,
            'phone' => $admission->guardian_phone,
        ]);
        $guardian->tenant_id = $tenant->id;

        try {
            app(GuardianNotifier::class)->send(
                $guardian,
                'Interview scheduled — '.$name.' — '.$tenant->name,
                [
                    "{$name} has been scheduled for an interview on {$dateLabel}. Please arrive 15 minutes early.",
                    'Application number: '.$admission->application_number,
                ],
                smsBody: "Dear {$admission->guardian_name}, {$name} has been scheduled for an interview on {$dateLabel}. Please arrive 15 minutes early. {$tenant->name}",
                actionLabel: 'Track Application',
                actionUrl: app(TenantUrlGenerator::class)->admissionStatus($tenant),
                schoolName: $tenant->name,
                replyToEmail: $tenant->email,
            );
        } catch (\Throwable $error) {
            Log::error('Admission interview guardian notification failed: '.$error->getMessage());
        }
    }
}
