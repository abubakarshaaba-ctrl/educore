<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\PlatformAgent;
use App\Models\AgentMessage;
use App\Models\AgentMessageRead;
use App\Models\StaffOfflineClockIn;
use App\Models\ClassLevelSubject;
use App\Models\StudentSubjectSelection;
use App\Models\Student;
use App\Models\StudentClassTransfer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // This app doesn't load Tailwind CSS, but Laravel's default pagination view does —
        // which renders unstyled, oversized SVG arrows on every paginated page. Use a
        // dependency-free custom view instead, everywhere ->links() is called.
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.custom');
        \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.custom');

        RateLimiter::for('tenant-login', function (Request $request) {
            return Limit::perMinute(5)->by($this->tenantAuthThrottleKey($request, 'login_id'));
        });

        RateLimiter::for('tenant-password', function (Request $request) {
            return Limit::perMinute(3)->by($this->tenantAuthThrottleKey($request, 'email'));
        });

        // Super-admin (platform) login: 10 attempts per minute per IP
        RateLimiter::for('global-login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Public contact / onboarding forms: 5 per minute per IP
        RateLimiter::for('public-form', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Public admissions portal: 10 submissions per minute per IP
        RateLimiter::for('public-admission', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->isSuperAdmin()) {
                return true;
            }

            return match ($ability) {
                'students.admit',
                'students.create',
                'students.edit'      => $user->canAccessExactModule('students'),
                'notifications.send' => $user->canAccessExactModule('notifications.send')
                    || $user->canAccessExactModule('notifications'),
                'scores.enter.own'   => $user->canAccessExactModule('scores.entry'),
                'scores.enter.all'   => $user->canAccessExactModule('scores'),
                'timetable.view.own' => $user->canAccessExactModule('timetable.view'),
                'timetable.view'     => $user->canAccessExactModule('timetable'),
                default              => null,
            };
        });

        // ── View composers: keep Eloquent/business logic out of Blade views ──

        // Agent portal chrome (session-based agent auth).
        View::composer('agent.layout', function ($view) {
            $agentId = session('agent_id');
            $view->with('currentAgent', $agentId ? PlatformAgent::find($agentId) : null);
            $view->with('unread', AgentMessage::whereNotIn(
                'id',
                AgentMessageRead::where('agent_id', $agentId)->pluck('message_id')
            )->count());
        });

        // Pending offline clock-in banner shown across every staff-attendance screen.
        View::composer('staff-attendance.*', function ($view) {
            $tenantId = optional(auth()->user())->tenant_id;
            $view->with('hasPendingOffline', $tenantId
                ? StaffOfflineClockIn::where('tenant_id', $tenantId)->where('status', 'pending')->exists()
                : false);
        });

        // Manual-override staff dropdown on the attendance index.
        View::composer('staff-attendance.index', function ($view) {
            if (! array_key_exists('allStaff', $view->getData())) {
                $tenantId = optional(auth()->user())->tenant_id;
                $view->with('allStaff', $tenantId
                    ? User::activeStaff($tenantId)->orderBy('name')->get()
                    : collect());
            }
        });

        // Curriculum: per-track level-rule counts.
        View::composer('curriculum.tracks', function ($view) {
            $tracks = $view->getData()['tracks'] ?? collect();
            $view->with('trackLevelCounts', collect($tracks)->mapWithKeys(fn ($t) => [
                $t->id => ClassLevelSubject::where('academic_track_id', $t->id)
                    ->distinct('class_level_id')->count(),
            ]));
        });

        // Curriculum: per-arm subject-rule counts.
        View::composer('curriculum.arm-tracks', function ($view) {
            $arms = $view->getData()['arms'] ?? collect();
            $counts = [];
            foreach (collect($arms)->flatten(1) as $arm) {
                $counts[$arm->id] = ClassLevelSubject::where('class_level_id', $arm->class_level_id)
                    ->where('is_active', true)
                    ->where('subject_status', '!=', 'not_offered')
                    ->where(function ($q) use ($arm) {
                        $q->whereNull('academic_track_id');
                        if ($arm->academic_track_id) {
                            $q->orWhere('academic_track_id', $arm->academic_track_id);
                        }
                    })->count();
            }
            $view->with('armSubjectCounts', $counts);
        });

        // Curriculum: a student's active subject selections.
        View::composer('curriculum.student-subjects', function ($view) {
            $data = $view->getData();
            $student = $data['student'] ?? null;
            $session = $data['session'] ?? null;
            $view->with('allSelected', $student
                ? StudentSubjectSelection::where('student_id', $student->id)
                    ->where('is_active', true)
                    ->when($session, fn ($q) => $q->where('session_id', $session->id))
                    ->with('subject')->get()
                : collect());
        });

        // Staff role metadata (constants surfaced as plain view data).
        View::composer(['staff.index', 'staff.archive.index', 'staff._role_select'], function ($view) {
            $view->with('roleLabels', User::ROLE_LABELS);
            $view->with('roleAccess', User::ROLE_ACCESS);
            $view->with('staffRoles', User::ROLES_STAFF);
            $view->with('staffArchiveStatuses', User::STAFF_ARCHIVE_STATUSES);
            $view->with('selected', User::canonicalRole($view->getData()['selected'] ?? ''));
        });

        // Student status constants surfaced as plain view data.
        View::composer(
            ['students.index', 'students.show', 'students.archive.index', 'students.class-transfers.show'],
            function ($view) {
                $view->with('studentStatuses', [
                    'applicant'       => Student::STATUS_APPLICANT,
                    'active'          => Student::STATUS_ACTIVE,
                    'suspended'       => Student::STATUS_SUSPENDED,
                    'left'            => Student::STATUS_LEFT,
                    'withdrawn'       => Student::STATUS_WITHDRAWN,
                    'transferred_out' => Student::STATUS_TRANSFERRED_OUT,
                    'graduated'       => Student::STATUS_GRADUATED,
                ]);
                $view->with('studentArchiveStatuses', Student::ARCHIVE_STATUSES);
                $view->with('transferPending', StudentClassTransfer::STATUS_PENDING);
            }
        );
    }

    private function tenantAuthThrottleKey(Request $request, string $field): string
    {
        $slug = Tenant::normalizeSlug((string) $request->route('slug'));
        $identifier = Str::lower(trim((string) $request->input($field, 'anonymous')));

        return implode('|', [
            $slug ?: 'unknown',
            hash('sha256', $identifier),
            $request->ip(),
        ]);
    }
}
