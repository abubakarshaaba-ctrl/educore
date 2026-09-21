<?php

namespace App\Providers;

use App\Contracts\LessonAiProvider;
use App\Http\Controllers\AssessmentTemplateController;
use App\Http\Controllers\StudentGuardianController;
use App\Services\Ai\GroqLessonProvider;
use App\Models\Tenant;
use App\Models\User;
use App\Models\PlatformAgent;
use App\Models\AgentMessage;
use App\Models\AgentMessageRead;
use App\Models\StaffOfflineClockIn;
use App\Models\ClassLevelSubject;
use App\Models\Guardian;
use App\Models\StudentSubjectSelection;
use App\Models\Student;
use App\Models\StudentClassTransfer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LessonAiProvider::class, GroqLessonProvider::class);
    }

    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.custom');
        \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.custom');

        // Defence in depth for multi-tenant identity isolation. This runs for
        // every User create/update path, including provisioning and portal flows,
        // rather than relying on individual controller validation alone.
        User::observe(\App\Observers\UserEmailSecurityObserver::class);

        // Platform broadcasts now use the canonical PlatformBroadcastController
        // delivery pipeline. Do not attach the legacy raw-query listener here:
        // doing so would send a second FCM notification for the same broadcast.

        Route::middleware(['web', 'auth', 'active.account', 'tenant'])
            ->post('/students/{student}/guardians', [StudentGuardianController::class, 'update'])
            ->name('students.guardians.update');

        Route::middleware(['web', 'auth', 'active.account', 'tenant'])
            ->prefix('assessment-templates')
            ->name('assessment-templates.')
            ->group(function (): void {
                Route::post('/', [AssessmentTemplateController::class, 'store'])->name('store');
                Route::put('/{template}', [AssessmentTemplateController::class, 'update'])->name('update');
                Route::post('/{template}/duplicate', [AssessmentTemplateController::class, 'duplicate'])->name('duplicate');
                Route::post('/{template}/assign', [AssessmentTemplateController::class, 'assign'])->name('assign');
                Route::delete('/{template}', [AssessmentTemplateController::class, 'destroy'])->name('destroy');
            });

        RateLimiter::for('tenant-login', fn (Request $request) => Limit::perMinute(5)->by($this->tenantAuthThrottleKey($request, 'login_id')));
        RateLimiter::for('tenant-password', fn (Request $request) => Limit::perMinute(3)->by($this->tenantAuthThrottleKey($request, 'email')));
        RateLimiter::for('global-login', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('public-form', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('public-admission', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->isSuperAdmin()) return true;
            return match ($ability) {
                'students.admit', 'students.create', 'students.edit' => $user->canAccessExactModule('students'),
                'notifications.send' => $user->canAccessExactModule('notifications.send') || $user->canAccessExactModule('notifications'),
                'scores.enter.own' => $user->canAccessExactModule('scores.entry'),
                'scores.enter.all' => $user->canAccessExactModule('scores'),
                'timetable.view.own' => $user->canAccessExactModule('timetable.view'),
                'timetable.view' => $user->canAccessExactModule('timetable'),
                default => null,
            };
        });

        View::composer('agent.layout', function ($view) {
            $agentId = session('agent_id');
            $view->with('currentAgent', $agentId ? PlatformAgent::find($agentId) : null);
            $view->with('unread', AgentMessage::whereNotIn('id', AgentMessageRead::where('agent_id', $agentId)->pluck('message_id'))->count());
        });

        View::composer('staff-attendance.*', function ($view) {
            $tenantId = optional(auth()->user())->tenant_id;
            $view->with('hasPendingOffline', $tenantId ? StaffOfflineClockIn::where('tenant_id', $tenantId)->where('status', 'pending')->exists() : false);
        });

        View::composer('staff-attendance.index', function ($view) {
            if (! array_key_exists('allStaff', $view->getData())) {
                $tenantId = optional(auth()->user())->tenant_id;
                $view->with('allStaff', $tenantId ? User::activeStaff($tenantId)->orderBy('name')->get() : collect());
            }
        });

        View::composer('scores.assessment-types', function ($view) {
            $tenantId = optional(auth()->user())->tenant_id;
            if (! $tenantId) return;
            $templates = \App\Models\AssessmentTemplate::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->with(['components', 'assignments.classLevel', 'assignments.session'])
                ->orderBy('name')->get();
            $selectedId = (int) request('selected', $templates->first()?->id ?? 0);
            $selectedTemplate = $templates->firstWhere('id', $selectedId) ?? $templates->first();
            $view->with('templates', $templates);
            $view->with('selectedTemplate', $selectedTemplate);
            $view->with('templateClassLevels', \App\Models\ClassLevel::withoutTenantScope()->where('tenant_id', $tenantId)->orderBy('order_index')->orderBy('name')->get());
            $view->with('templateSessions', \App\Models\AcademicSession::withoutTenantScope()->where('tenant_id', $tenantId)->orderByDesc('id')->get());
        });

        View::composer(['scores.entry', 'scores.broadsheet'], function ($view) {
            $data = $view->getData();
            $classArm = $data['classArm'] ?? null;
            $term = $data['term'] ?? null;
            if (! $classArm || ! $term || ! $classArm->class_level_id) return;

            $resolved = \App\Models\AssessmentType::resolvedForClassLevel((int) $term->id, (int) $classArm->class_level_id);
            $view->with('assessmentTypes', $resolved);

            if ($view->getName() === 'scores.entry') {
                $students = collect($data['students'] ?? []);
                $existingScores = $data['existingScores'] ?? [];
                $studentTotals = [];
                foreach ($students as $student) {
                    $studentTotals[$student->id] = $resolved->sum(
                        fn ($type) => (float) ($existingScores[$student->id][$type->id] ?? 0)
                    );
                }
                $view->with('studentTotals', $studentTotals);
            }
        });

        View::composer('curriculum.tracks', function ($view) {
            $tracks = $view->getData()['tracks'] ?? collect();
            $view->with('trackLevelCounts', collect($tracks)->mapWithKeys(fn ($t) => [$t->id => ClassLevelSubject::where('academic_track_id', $t->id)->distinct('class_level_id')->count()]));
        });

        View::composer('curriculum.arm-tracks', function ($view) {
            $arms = $view->getData()['arms'] ?? collect();
            $counts = [];
            foreach (collect($arms)->flatten(1) as $arm) {
                $counts[$arm->id] = ClassLevelSubject::where('class_level_id', $arm->class_level_id)
                    ->where('is_active', true)->where('subject_status', '!=', 'not_offered')
                    ->where(function ($q) use ($arm) {
                        $q->whereNull('academic_track_id');
                        if ($arm->academic_track_id) $q->orWhere('academic_track_id', $arm->academic_track_id);
                    })->count();
            }
            $view->with('armSubjectCounts', $counts);
        });

        View::composer('curriculum.student-subjects', function ($view) {
            $data = $view->getData();
            $student = $data['student'] ?? null;
            $session = $data['session'] ?? null;
            $view->with('allSelected', $student ? StudentSubjectSelection::where('student_id', $student->id)->where('is_active', true)->when($session, fn ($q) => $q->where('session_id', $session->id))->with('subject')->get() : collect());
        });

        View::composer('students.edit', function ($view) {
            $view->with('availableGuardians', Guardian::query()->with(['students:id,first_name,last_name,admission_number', 'user:id'])->orderBy('first_name')->orderBy('last_name')->get());
        });

        View::composer(['staff.index', 'staff.archive.index', 'staff._role_select'], function ($view) {
            $view->with('roleLabels', User::ROLE_LABELS);
            $view->with('roleAccess', User::ROLE_ACCESS);
            $view->with('staffRoles', User::ROLES_STAFF);
            $view->with('staffArchiveStatuses', User::STAFF_ARCHIVE_STATUSES);
            $view->with('selected', User::canonicalRole($view->getData()['selected'] ?? ''));
        });

        View::composer(['students.index', 'students.show', 'students.archive.index', 'students.class-transfers.show'], function ($view) {
            $view->with('studentStatuses', [
                'applicant' => Student::STATUS_APPLICANT,
                'active' => Student::STATUS_ACTIVE,
                'suspended' => Student::STATUS_SUSPENDED,
                'left' => Student::STATUS_LEFT,
                'withdrawn' => Student::STATUS_WITHDRAWN,
                'transferred_out' => Student::STATUS_TRANSFERRED_OUT,
                'graduated' => Student::STATUS_GRADUATED,
            ]);
            $view->with('studentArchiveStatuses', Student::ARCHIVE_STATUSES);
            $view->with('transferPending', StudentClassTransfer::STATUS_PENDING);
        });
    }

    private function tenantAuthThrottleKey(Request $request, string $field): string
    {
        $slug = Tenant::normalizeSlug((string) $request->route('slug'));
        $identifier = Str::lower(trim((string) $request->input($field, 'anonymous')));
        return implode('|', [$slug ?: 'unknown', hash('sha256', $identifier), $request->ip()]);
    }
}
