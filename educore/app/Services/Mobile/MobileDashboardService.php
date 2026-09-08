<?php

namespace App\Services\Mobile;

use App\Http\Controllers\Api\AccountantController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\HealthOfficerController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TransportOfficerController;
use App\Models\AcademicSession;
use App\Models\Announcement;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\ExamSupervisor;
use App\Models\StaffAttendanceRecord;
use App\Models\Student;
use App\Models\TimetablePeriod;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MobileDashboardService
{
    /**
     * These workspaces are intentionally hosted inside the staff More hub.
     * Keeping them out of Home quick actions prevents a dashboard tile from
     * bypassing the hub-owned native navigation state.
     */
    private const STAFF_MORE_ONLY_MODULES = [
        'staff',
        'transfers',
        'risk',
        'exports',
        'cbt',
        'cbt-exams',
        'examinations',
    ];

    public function __construct(private readonly MobileModuleService $modules) {}

    public function for(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $scope = $this->scopeFor($user);
        $raw = $scope === 'staff'
            ? $this->staffPayload($request)
            : $this->existingDashboardPayload($scope, $request);

        return [
            'contract_version' => 1,
            'scope' => $scope,
            'role_key' => $user->roleKey(),
            'generated_at' => now()->toIso8601String(),
            'metrics' => $this->metrics($scope, $raw),
            'quick_actions' => $this->quickActions($user),
            'sections' => $this->sections($scope, $raw),
        ];
    }

    private function scopeFor(User $user): string
    {
        return match (true) {
            $user->isSuperAdmin() => 'platform',
            $user->isStudent() => 'student',
            $user->isParent() => 'parent',
            $user->isAccountant() => 'accountant',
            in_array($user->roleKey(), ['admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator'], true) => 'admin',
            $user->roleKey() === 'transport_officer' => 'transport',
            $user->roleKey() === 'health_officer' => 'health',
            default => 'staff',
        };
    }

    private function existingDashboardPayload(string $scope, Request $request): array
    {
        $response = match ($scope) {
            'platform' => app(PlatformController::class)->dashboard($request),
            'student' => app(StudentController::class)->dashboard($request),
            'parent' => app(ParentController::class)->dashboard($request),
            'accountant' => app(AccountantController::class)->dashboard($request),
            'admin' => app(AdminController::class)->dashboard($request),
            'transport' => app(TransportOfficerController::class)->dashboard($request),
            'health' => app(HealthOfficerController::class)->dashboard($request),
            default => response()->json([]),
        };

        return $this->jsonData($response);
    }

    private function staffPayload(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $session = AcademicSession::current()->first();
        $tutorArmIds = ClassArm::where('form_tutor_id', $user->id)->pluck('id');
        $subjectArmIds = ClassArmSubject::where('teacher_id', $user->id)->pluck('class_arm_id');
        $armIds = $tutorArmIds->merge($subjectArmIds)->unique()->values();

        $classes = ClassArm::with('classLevel:id,name')
            ->whereIn('id', $armIds)
            ->withCount(['students' => fn ($query) => $query->where('status', Student::STATUS_ACTIVE)])
            ->get()
            ->map(fn (ClassArm $arm): array => [
                'id' => $arm->id,
                'name' => $arm->full_name,
                'students' => $arm->students_count,
                'role' => $tutorArmIds->contains($arm->id) ? 'Form tutor' : 'Subject teacher',
            ]);

        $today = collect();
        if (Schema::hasTable('timetable_periods')) {
            $today = TimetablePeriod::with(['classArm.classLevel:id,name', 'subject:id,name'])
                ->where('teacher_id', $user->id)
                ->whereRaw('LOWER(day_of_week) = ?', [strtolower(now()->format('l'))])
                ->when($session, fn ($query) => $query->where('session_id', $session->id))
                ->orderBy('start_time')
                ->get()
                ->map(fn (TimetablePeriod $period): array => [
                    'id' => $period->id,
                    'title' => $period->subject?->name ?? 'Teaching period',
                    'subtitle' => $period->classArm?->full_name,
                    'supporting_text' => trim(substr((string) $period->start_time, 0, 5).'–'.substr((string) $period->end_time, 0, 5).' '.($period->venue ?? '')),
                    'status' => null,
                    'timestamp' => null,
                    'module_key' => 'timetable',
                ]);
        }

        $duties = collect();
        if (Schema::hasTable('exam_supervisors')) {
            $duties = ExamSupervisor::with(['entry.classLevel', 'entry.subject', 'entry.examSession', 'entry.examPeriod'])
                ->where('user_id', $user->id)
                ->whereHas('entry.examPeriod', fn ($query) => $query->where('status', 'published'))
                ->get()
                ->filter(fn ($duty) => $duty->entry?->exam_date?->startOfDay()->gte(today()))
                ->sortBy(fn ($duty) => $duty->entry->exam_date->toDateString().'-'.($duty->entry->examSession?->sort_order ?? 0))
                ->take(5)
                ->map(fn ($duty): array => [
                    'id' => $duty->id,
                    'title' => trim(($duty->entry?->subject?->name ?? 'Exam').' · '.($duty->entry?->classLevel?->name ?? '')),
                    'subtitle' => $duty->entry?->examPeriod?->title,
                    'supporting_text' => trim(($duty->entry?->examSession?->name ?? '').' · '.($duty->entry?->venue ?? '')),
                    'status' => 'Duty',
                    'timestamp' => $duty->entry?->exam_date?->toDateString(),
                    'module_key' => 'timetable',
                ])->values();
        }

        $attendance = null;
        if (Schema::hasTable('staff_attendance_records')) {
            $attendance = StaffAttendanceRecord::where('user_id', $user->id)
                ->whereDate('attendance_date', today())
                ->first();
        }

        return [
            'metrics' => [
                'classes' => $classes->count(),
                'students' => $classes->sum('students'),
                'today_periods' => $today->count(),
                'upcoming_duties' => $duties->count(),
            ],
            'attendance_status' => $attendance?->statusLabel() ?? 'Not recorded',
            'classes' => $classes,
            'today' => $today,
            'duties' => $duties,
            'announcements' => $this->announcements(['all', 'staff']),
        ];
    }

    private function metrics(string $scope, array $raw): array
    {
        $metrics = match ($scope) {
            'platform' => [
                $this->metric('schools', 'Schools', data_get($raw, 'metrics.schools', 0), 'number', 'brand', 'platform.schools'),
                $this->metric('active_schools', 'Active schools', data_get($raw, 'metrics.active_schools', 0), 'number', 'success', 'platform.schools'),
                $this->metric('students', 'Students', data_get($raw, 'metrics.students', 0), 'number', 'info', 'platform.analytics'),
                $this->metric('monthly_revenue', 'Revenue this month', data_get($raw, 'metrics.monthly_revenue', 0), 'currency', 'accent', 'platform.billing'),
            ],
            'admin' => [
                $this->metric('students', 'Students', data_get($raw, 'metrics.students', 0), 'number', 'brand', 'students'),
                $this->metric('staff', 'Staff', data_get($raw, 'metrics.staff', 0), 'number', 'info', 'staff'),
                $this->metric('attendance_rate', 'Attendance today', data_get($raw, 'metrics.attendance_rate'), 'percentage', 'success', 'attendance'),
                $this->metric('pending_admissions', 'Pending admissions', data_get($raw, 'metrics.pending_admissions', 0), 'number', 'warning', 'admissions'),
            ],
            'student' => [
                $this->metric('average', 'Term average', data_get($raw, 'summary.average'), 'percentage', 'brand', 'student.results'),
                $this->metric('attendance_rate', 'Attendance', data_get($raw, 'attendance.rate'), 'percentage', 'success', 'student.attendance'),
                $this->metric('position', 'Class position', data_get($raw, 'summary.position'), 'ordinal', 'accent', 'student.results'),
                $this->metric('upcoming_exams', 'Upcoming exams', count(data_get($raw, 'upcoming_exams', [])), 'number', 'info', 'student.exams'),
            ],
            'parent' => [
                $this->metric('children', 'Children', count(data_get($raw, 'children', [])), 'number', 'brand', 'parent.dashboard'),
                $this->metric('attendance_rate', 'Attendance', data_get($raw, 'attendance.rate'), 'percentage', 'success', 'parent.attendance'),
                $this->metric('average', 'Term average', data_get($raw, 'result.average'), 'percentage', 'info', 'parent.results'),
                $this->metric('outstanding', 'Outstanding', data_get($raw, 'outstanding_balance', 0), 'currency', 'warning', 'parent.fees'),
            ],
            'accountant' => [
                $this->metric('billed', 'Total billed', data_get($raw, 'summary.billed', 0), 'currency', 'brand', 'fees'),
                $this->metric('collected', 'Collected', data_get($raw, 'summary.collected', 0), 'currency', 'success', 'fees'),
                $this->metric('outstanding', 'Outstanding', data_get($raw, 'summary.outstanding', 0), 'currency', 'warning', 'fees'),
                $this->metric('expenses', 'Expenses', data_get($raw, 'summary.expenses', 0), 'currency', 'danger', 'expenses'),
            ],
            'transport' => [
                $this->metric('routes', 'Routes', data_get($raw, 'metrics.routes', 0), 'number', 'brand', 'transport'),
                $this->metric('active_buses', 'Active buses', data_get($raw, 'metrics.active_buses', 0), 'number', 'success', 'transport'),
                $this->metric('assigned_students', 'Assigned riders', data_get($raw, 'metrics.assigned_students', 0), 'number', 'info', 'transport'),
                $this->metric('unassigned_students', 'Unassigned', data_get($raw, 'metrics.unassigned_students', 0), 'number', 'warning', 'transport'),
            ],
            'health' => [
                $this->metric('students', 'Students', data_get($raw, 'metrics.students', 0), 'number', 'brand', 'health'),
                $this->metric('records', 'Health records', data_get($raw, 'metrics.records', 0), 'number', 'success', 'health'),
                $this->metric('allergy_alerts', 'Allergy alerts', data_get($raw, 'metrics.allergy_alerts', 0), 'number', 'danger', 'health'),
                $this->metric('medication_alerts', 'Medication alerts', data_get($raw, 'metrics.medication_alerts', 0), 'number', 'warning', 'health'),
            ],
            default => [
                $this->metric('classes', 'Assigned classes', data_get($raw, 'metrics.classes', 0), 'number', 'brand', 'classes'),
                $this->metric('students', 'Students', data_get($raw, 'metrics.students', 0), 'number', 'success', 'students'),
                $this->metric('today_periods', 'Periods today', data_get($raw, 'metrics.today_periods', 0), 'number', 'info', 'timetable'),
                $this->metric('exam_duties', 'Upcoming duties', data_get($raw, 'metrics.upcoming_duties', 0), 'number', 'accent', 'timetable'),
            ],
        };

        return array_values($metrics);
    }

    private function sections(string $scope, array $raw): array
    {
        $sections = match ($scope) {
            'platform' => [
                $this->section('attention', 'Needs attention', collect(data_get($raw, 'attention', []))->filter()->map(
                    fn ($value, $key): array => $this->item($key, (string) str($key)->replace('_', ' ')->title(), (string) $value, status: 'Review', moduleKey: 'platform.schools')
                )->values()),
                $this->section('recent_schools', 'Recently added schools', collect(data_get($raw, 'recent_schools', []))->map(
                    fn ($school): array => $this->item(data_get($school, 'id'), data_get($school, 'name', 'School'), data_get($school, 'plan'), data_get($school, 'students').' students', data_get($school, 'status'), moduleKey: 'platform.schools')
                )),
            ],
            'student' => [
                $this->section('upcoming_exams', 'Upcoming examinations', collect(data_get($raw, 'upcoming_exams', []))->map(
                    fn ($exam): array => $this->item(data_get($exam, 'id'), data_get($exam, 'title', 'Examination'), data_get($exam, 'subject'), data_get($exam, 'duration_minutes').' minutes', data_get($exam, 'status'), data_get($exam, 'scheduled_start'), 'student.exams')
                )),
                $this->section('announcements', 'Announcements', $this->announcementItems(data_get($raw, 'announcements', []), 'student.dashboard')),
            ],
            'parent' => [
                $this->section('children', 'Children', collect(data_get($raw, 'children', []))->map(
                    fn ($child): array => $this->item(data_get($child, 'id'), data_get($child, 'name', 'Student'), data_get($child, 'class.name'), data_get($child, 'admission_number'), data_get($child, 'status'), moduleKey: 'parent.dashboard')
                )),
                $this->section('announcements', 'Announcements', $this->announcementItems(data_get($raw, 'announcements', []), 'parent.notifications')),
            ],
            'accountant' => [
                $this->section('recent_invoices', 'Recent invoices', collect(data_get($raw, 'invoices', []))->take(6)->map(
                    fn ($invoice): array => $this->item(data_get($invoice, 'id'), data_get($invoice, 'student', 'Invoice'), data_get($invoice, 'number'), 'Balance '.$this->display(data_get($invoice, 'balance', 0), 'currency'), data_get($invoice, 'status'), moduleKey: 'fees')
                )),
            ],
            'transport' => [
                $this->section('routes', 'Transport routes', collect(data_get($raw, 'routes', []))->take(6)->map(
                    fn ($route): array => $this->item(data_get($route, 'id'), data_get($route, 'name', 'Route'), data_get($route, 'bus'), data_get($route, 'riders', 0).' riders', data_get($route, 'active') ? 'Active' : 'Inactive', moduleKey: 'transport')
                )),
            ],
            'health' => [
                $this->section('alerts', 'Health alerts', collect(data_get($raw, 'students', []))->filter(
                    fn ($student) => data_get($student, 'allergy_alert') || data_get($student, 'medication_alert')
                )->take(8)->map(
                    fn ($student): array => $this->item(data_get($student, 'id'), data_get($student, 'name', 'Student'), data_get($student, 'class'), data_get($student, 'admission_number'), 'Attention', moduleKey: 'health')
                )->values()),
            ],
            'staff' => [
                $this->section('today', "Today's schedule", collect(data_get($raw, 'today', []))),
                $this->section('duties', 'Upcoming exam duties', collect(data_get($raw, 'duties', []))),
                $this->section('classes', 'Assigned classes', collect(data_get($raw, 'classes', []))->map(
                    fn ($class): array => $this->item(data_get($class, 'id'), data_get($class, 'name', 'Class'), data_get($class, 'role'), data_get($class, 'students', 0).' students', moduleKey: 'classes')
                )),
                $this->section('announcements', 'Announcements', $this->announcementItems(data_get($raw, 'announcements', []), 'notifications.view')),
            ],
            default => [
                $this->section('announcements', 'Announcements', $this->announcementItems($this->announcements(['all', 'staff']), 'notifications.view')),
            ],
        };

        if ($scope === 'admin') {
            $sections = [
                $this->section('operations', 'Academic operations', collect(data_get($raw, 'operations', []))->map(
                    fn ($value, $key): array => $this->item($key, (string) str($key)->replace('_', ' ')->title(), (string) $value, moduleKey: $key === 'attendance_marked' ? 'attendance' : $key)
                )->values()),
                $this->section('announcements', 'Announcements', $this->announcementItems($this->announcements(['all', 'staff']), 'notifications.view')),
            ];
        }

        return collect($sections)
            ->filter(fn (array $section): bool => count($section['items']) > 0)
            ->values()
            ->all();
    }

    private function quickActions(User $user): array
    {
        $modules = collect($this->modules->forUser($user))
            ->reject(fn (array $module): bool => str_ends_with($module['key'], 'dashboard') || $module['key'] === 'dashboard');

        if (!$user->isSuperAdmin() && !$user->isStudent() && !$user->isParent()) {
            $modules = $modules->reject(
                fn (array $module): bool => in_array(strtolower($module['key']), self::STAFF_MORE_ONLY_MODULES, true)
            );
        }

        return $modules
            ->take(6)
            ->map(fn (array $module): array => [
                'module_key' => $module['key'],
                'title' => $module['title'],
                'icon' => $module['icon'],
                'path' => $module['path'],
            ])->values()->all();
    }

    private function announcements(array $audiences): Collection
    {
        if (! Schema::hasTable('announcements')) {
            return collect();
        }

        return Announcement::where('is_published', true)
            ->whereIn('audience', $audiences)
            ->where(fn ($query) => $query->whereNull('expire_date')->orWhere('expire_date', '>=', now()->toDateString()))
            ->latest(Schema::hasColumn('announcements', 'publish_date') ? 'publish_date' : 'created_at')
            ->limit(6)
            ->get(['id', 'title', 'body', 'priority', 'publish_date']);
    }

    private function announcementItems(iterable $items, string $moduleKey): Collection
    {
        return collect($items)->map(fn ($announcement): array => $this->item(
            data_get($announcement, 'id'),
            data_get($announcement, 'title', 'Announcement'),
            (string) str((string) data_get($announcement, 'body'))->squish()->limit(120),
            status: data_get($announcement, 'priority'),
            timestamp: data_get($announcement, 'publish_date'),
            moduleKey: $moduleKey,
        ));
    }

    private function metric(string $key, string $label, mixed $value, string $format, string $tone, ?string $moduleKey = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'display_value' => $this->display($value, $format),
            'tone' => $tone,
            'module_key' => $moduleKey,
        ];
    }

    private function display(mixed $value, string $format): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($format) {
            'currency' => '₦'.number_format((float) $value, 0),
            'percentage' => rtrim(rtrim(number_format((float) $value, 1), '0'), '.').'%',
            'ordinal' => is_numeric($value) ? (string) ((int) $value) : (string) $value,
            default => is_numeric($value) ? number_format((float) $value, ((float) $value === (float) (int) $value) ? 0 : 1) : (string) $value,
        };
    }

    private function section(string $key, string $title, Collection $items): array
    {
        return ['key' => $key, 'title' => $title, 'items' => $items->values()->all()];
    }

    private function item(mixed $id, string $title, ?string $subtitle = null, ?string $supportingText = null, ?string $status = null, mixed $timestamp = null, ?string $moduleKey = null): array
    {
        return [
            'id' => (string) ($id ?? md5($title.$subtitle)),
            'title' => $title,
            'subtitle' => filled($subtitle) ? $subtitle : null,
            'supporting_text' => filled($supportingText) ? $supportingText : null,
            'status' => filled($status) ? $status : null,
            'timestamp' => filled($timestamp) ? (string) $timestamp : null,
            'module_key' => $moduleKey,
        ];
    }

    private function jsonData(JsonResponse $response): array
    {
        return (array) $response->getData(true);
    }
}
