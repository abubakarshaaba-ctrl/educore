<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AdmissionPortalSetting;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\GradingSystem;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Services\Auth\AuthAuditLogger;
use App\Services\TenantOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantOnboardingController extends Controller
{
    public function __construct(
        private readonly TenantOnboardingService $onboarding,
        private readonly AuthAuditLogger $audit
    ) {
    }

    public function index(Request $request): View
    {
        $tenant = $this->tenant($request);
        $status = $this->onboarding->status($tenant);

        return view('tenant.onboarding.index', compact('tenant', 'status'));
    }

    public function profile(Request $request): View
    {
        $tenant = $this->tenant($request);
        $status = $this->onboarding->status($tenant);

        return view('tenant.onboarding.profile', compact('tenant', 'status'));
    }

    public function saveProfile(Request $request): RedirectResponse
    {
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:300'],
            'motto' => ['nullable', 'string', 'max:200'],
        ]);

        $old = $tenant->only(array_keys($data));
        $tenant->update($data);
        $this->recordStep($request, $tenant, 'profile', $old, $data);

        return redirect()->route('tenant.onboarding.branding')
            ->with('success', 'School profile saved.');
    }

    public function branding(Request $request): View
    {
        $tenant = $this->tenant($request);
        $status = $this->onboarding->status($tenant);

        return view('tenant.onboarding.branding', compact('tenant', 'status'));
    }

    public function saveBranding(Request $request): RedirectResponse
    {
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'theme_primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_sidebar' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $old = $tenant->only(['theme_primary', 'theme_accent', 'theme_sidebar', 'logo_path']);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store("logos/{$tenant->id}", 'public');
            if ($tenant->logo_path && $tenant->logo_path !== $path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $data['logo_path'] = $path;
        }

        unset($data['logo']);
        $tenant->update($data);
        $this->recordStep($request, $tenant, 'branding', $old, $data);

        return redirect()->route('tenant.onboarding.session')
            ->with('success', 'Branding saved.');
    }

    public function academicSession(Request $request): View
    {
        $tenant = $this->tenant($request);
        $status = $this->onboarding->status($tenant);
        $sessions = AcademicSession::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->with('terms')
            ->orderByDesc('is_current')
            ->latest()
            ->get();

        return view('tenant.onboarding.academic-session', compact('tenant', 'status', 'sessions'));
    }

    public function saveAcademicSession(Request $request): RedirectResponse
    {
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'session_name' => ['required', 'string', 'max:80'],
            'term_name' => ['required', 'string', 'max:80'],
            'term_start_date' => ['required', 'date'],
            'term_end_date' => ['required', 'date', 'after_or_equal:term_start_date'],
        ]);

        DB::transaction(function () use ($tenant, $data) {
            AcademicSession::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->update(['is_current' => false]);
            Term::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->update(['is_current' => false]);

            $session = AcademicSession::withoutTenantScope()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $data['session_name']],
                ['is_current' => true]
            );

            Term::withoutTenantScope()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'session_id' => $session->id,
                    'name' => $data['term_name'],
                ],
                [
                    'start_date' => $data['term_start_date'],
                    'end_date' => $data['term_end_date'],
                    'is_current' => true,
                ]
            );
        });

        $this->recordStep($request, $tenant, 'academic-session', [], $data);

        return redirect()->route('tenant.onboarding.classes')
            ->with('success', 'Current academic session and term saved.');
    }

    public function classes(Request $request): View
    {
        $tenant = $this->tenant($request);
        $status = $this->onboarding->status($tenant);
        $levels = ClassLevel::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->with('classArms')
            ->orderBy('order_index')
            ->get();

        return view('tenant.onboarding.classes', compact('tenant', 'status', 'levels'));
    }

    public function saveClasses(Request $request): RedirectResponse
    {
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'level_name' => ['required', 'string', 'max:80'],
            'section' => ['required', 'in:creche,nursery,primary,junior_secondary,senior_secondary'],
            'arm_name' => ['required', 'string', 'max:20'],
            'order_index' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($tenant, $data) {
            $level = ClassLevel::withoutTenantScope()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $data['level_name']],
                [
                    'section' => $data['section'],
                    'order_index' => $data['order_index'] ?? 0,
                ]
            );

            ClassArm::withoutTenantScope()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'class_level_id' => $level->id,
                    'name' => $data['arm_name'],
                ]
            );
        });

        $this->recordStep($request, $tenant, 'classes', [], $data);

        return redirect()->route('tenant.onboarding.subjects')
            ->with('success', 'Class level and arm saved.');
    }

    public function subjects(Request $request): View
    {
        $tenant = $this->tenant($request);
        $status = $this->onboarding->status($tenant);
        $subjects = Subject::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();
        $levels = ClassLevel::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->orderBy('order_index')
            ->get();
        $grading = GradingSystem::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->orderBy('class_level_id')
            ->orderByDesc('min_score')
            ->get();

        return view('tenant.onboarding.subjects', compact('tenant', 'status', 'subjects', 'levels', 'grading'));
    }

    public function saveSubjects(Request $request): RedirectResponse
    {
        $tenant = $this->tenant($request);
        $action = $request->input('setup_action', 'subject');

        if ($action === 'grade') {
            $data = $request->validate([
                'class_level_id' => ['required', Rule::exists('class_levels', 'id')->where('tenant_id', $tenant->id)],
                'grade_letter' => ['required', 'string', 'max:5'],
                'min_score' => ['required', 'integer', 'min:0', 'max:100'],
                'max_score' => ['required', 'integer', 'min:0', 'max:100', 'gte:min_score'],
                'remark' => ['required', 'string', 'max:50'],
                'is_pass_grade' => ['nullable', 'boolean'],
            ]);
            $data['tenant_id'] = $tenant->id;
            $data['is_pass_grade'] = $request->boolean('is_pass_grade', true);

            GradingSystem::withoutTenantScope()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'class_level_id' => $data['class_level_id'],
                    'grade_letter' => $data['grade_letter'],
                ],
                $data
            );
        } else {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:100'],
                'code' => ['nullable', 'string', 'max:20'],
            ]);
            $data['tenant_id'] = $tenant->id;
            $data['is_active'] = true;

            Subject::withoutTenantScope()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $data['name']],
                $data
            );
        }

        $this->recordStep($request, $tenant, 'subjects', [], ['action' => $action]);

        return back()->with('success', $action === 'grade' ? 'Grading rule saved.' : 'Subject saved.');
    }

    public function settings(Request $request): View
    {
        $tenant = $this->tenant($request);
        $status = $this->onboarding->status($tenant);
        $portal = AdmissionPortalSetting::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->first();
        $settings = SchoolSetting::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->keyBy('key');

        return view('tenant.onboarding.settings', compact('tenant', 'status', 'portal', 'settings'));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'is_open' => ['nullable', 'boolean'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'application_fee' => ['nullable', 'numeric', 'min:0'],
            'welcome_message' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:180'],
            'proprietor' => ['nullable', 'string', 'max:120'],
            'slogan' => ['nullable', 'string', 'max:180'],
        ]);

        AdmissionPortalSetting::withoutTenantScope()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'is_open' => $request->boolean('is_open'),
                'academic_year' => $data['academic_year'] ?? null,
                'application_fee' => $data['application_fee'] ?? 0,
                'welcome_message' => $data['welcome_message'] ?? null,
            ]
        );

        foreach (['website', 'proprietor', 'slogan'] as $key) {
            if (array_key_exists($key, $data)) {
                SchoolSetting::withoutTenantScope()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'key' => $key],
                    ['value' => $data[$key], 'group' => 'general']
                );
            }
        }

        $this->recordStep($request, $tenant, 'settings', [], ['keys' => array_keys($data)]);

        return redirect()->route('tenant.onboarding.portals')
            ->with('success', 'Operational settings saved.');
    }

    public function portals(Request $request): View
    {
        $tenant = $this->tenant($request);
        $status = $this->onboarding->status($tenant);

        return view('tenant.onboarding.portals', compact('tenant', 'status'));
    }

    public function review(Request $request): View
    {
        $tenant = $this->tenant($request);
        $status = $this->onboarding->status($tenant);

        return view('tenant.onboarding.review', compact('tenant', 'status'));
    }

    public function complete(Request $request): RedirectResponse
    {
        $tenant = $this->tenant($request);
        $status = $this->onboarding->status($tenant);

        if (!$status->can_activate) {
            $this->audit->recordForTenant($tenant, 'tenant.onboarding.activation_denied', [
                'blocking_count' => count($status->blocking_items),
                'warning_count' => count($status->warning_items),
            ], $request, 'blocking_items_remaining', $request->user());

            return back()->withErrors(['onboarding' => 'Resolve blocking readiness items before completing onboarding.']);
        }

        $this->audit->recordForTenant($tenant, 'tenant.onboarding.completed', [
            'warning_count' => count($status->warning_items),
            'progress_percentage' => $status->progress_percentage,
        ], $request, null, $request->user());

        return redirect()->route('dashboard')->with('success', 'Onboarding review completed. School operations are available.');
    }

    private function tenant(Request $request): Tenant
    {
        $user = $request->user();
        $tenant = $request->attributes->get('current_tenant') ?: $user?->tenant;

        abort_unless($tenant instanceof Tenant, 404);
        abort_unless($this->canManageOnboarding($request), 403);

        return $tenant;
    }

    private function canManageOnboarding(Request $request): bool
    {
        $user = $request->user();

        return $user
            && $user->isTenantStaff()
            && ($user->canAccessModule('settings') || $request->session()->has('super_admin_id'));
    }

    private function recordStep(Request $request, Tenant $tenant, string $step, array $oldValues, array $newValues): void
    {
        $this->audit->recordForTenant($tenant, 'tenant.onboarding.step_completed', [
            'step' => $step,
            'old_values' => $oldValues,
            'new_values' => $this->safeAuditValues($newValues),
        ], $request, null, $request->user());
    }

    private function safeAuditValues(array $values): array
    {
        unset($values['password'], $values['admin_password'], $values['token'], $values['_token']);

        return $values;
    }
}
