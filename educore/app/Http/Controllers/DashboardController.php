<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\Invoice;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentRiskFlag;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->is_super_admin) {
            return $this->superDashboard();
        }

        // Staff self-service uses the unified portal shell and never receives
        // school-wide fee totals, all-student lists, or administration widgets.
        if (!$user->canAccessExactModule('students')) {
            return redirect()->route('staff.portal.dashboard');
        }

        return $this->schoolDashboard();
    }

    private function schoolDashboard()
    {
        $tenantId = auth()->user()->tenant_id;
        $currentTerm = Term::where('is_current', true)->first();

        // ── Core Stats ────────────────────────────────────────────────
        $totalStudents   = Student::where('status', Student::STATUS_ACTIVE)->count();
        $totalStaff      = User::activeStaff($tenantId)->count();
        $totalClasses    = ClassArm::count();

        // ── Fee Stats ─────────────────────────────────────────────────
        $totalInvoiced   = Invoice::when($currentTerm, fn($q) => $q->where('term_id', $currentTerm->id))->sum('total_amount');
        $totalCollected  = Invoice::when($currentTerm, fn($q) => $q->where('term_id', $currentTerm->id))->sum('amount_paid');
        $totalOutstanding= $totalInvoiced - $totalCollected;
        $collectionRate  = $totalInvoiced > 0 ? round(($totalCollected / $totalInvoiced) * 100, 1) : 0;

        // ── Attendance (today) ────────────────────────────────────────
        $today           = today()->toDateString();
        $todayAttendance = AttendanceRecord::where('attendance_date', $today)->get();
        $presentToday    = $todayAttendance->where('status', 'present')->count();
        $absentToday     = $todayAttendance->where('status', '!=', 'present')->count();
        $attendanceRate  = ($presentToday + $absentToday) > 0
                           ? round(($presentToday / ($presentToday + $absentToday)) * 100, 1)
                           : null;

        // ── Weekly Attendance Trend (last 7 days) ─────────────────────
        $attendanceTrend = AttendanceRecord::selectRaw(
            'attendance_date, COUNT(*) as total, SUM(CASE WHEN status="present" THEN 1 ELSE 0 END) as present'
        )
        ->where('attendance_date', '>=', now()->subDays(6)->toDateString())
        ->groupBy('attendance_date')
        ->orderBy('attendance_date')
        ->get()
        ->map(fn($r) => [
            'date'    => Carbon::parse($r->attendance_date)->format('D'),
            'rate'    => $r->total > 0 ? round(($r->present / $r->total) * 100) : 0,
            'present' => $r->present,
            'total'   => $r->total,
        ]);

        // ── Fee Collection Monthly Trend (last 6 months) ──────────────
        $feesTrend = Invoice::selectRaw(
            'MONTH(updated_at) as month, YEAR(updated_at) as year, SUM(amount_paid) as collected'
        )
        ->where('amount_paid', '>', 0)
        ->where('updated_at', '>=', now()->subMonths(5)->startOfMonth())
        ->groupBy('year', 'month')
        ->orderBy('year')->orderBy('month')
        ->get()
        ->map(fn($r) => [
            'label'     => Carbon::create($r->year, $r->month)->format('M'),
            'collected' => (float)$r->collected,
        ]);

        // ── Students by Class Level ───────────────────────────────────
        $studentsByClass = ClassArm::with('classLevel')
            ->withCount(['students as student_count' => fn($q) => $q->where('status', Student::STATUS_ACTIVE)])
            ->having('student_count', '>', 0)
            ->orderBy('class_level_id')
            ->get()
            ->map(fn($a) => [
                'label' => optional($a->classLevel)->name . ' ' . $a->name,
                'count' => $a->student_count,
            ]);

        // ── Enrollment Growth (last 6 months, cumulative active students) ──
        $enrollmentGrowth = collect(range(5, 0))->map(function ($monthsAgo) {
            $cutoff = now()->subMonths($monthsAgo)->endOfMonth();
            return [
                'label' => $cutoff->format('M'),
                'count' => Student::where('status', Student::STATUS_ACTIVE)
                    ->where('admission_date', '<=', $cutoff)
                    ->count(),
            ];
        });
        $enrollmentGrowthPct = null;
        if ($enrollmentGrowth->first()['count'] > 0) {
            $enrollmentGrowthPct = round(
                (($enrollmentGrowth->last()['count'] - $enrollmentGrowth->first()['count']) / $enrollmentGrowth->first()['count']) * 100,
                1
            );
        }
        $newAdmissionsThisMonth = Student::where('status', Student::STATUS_ACTIVE)
            ->whereMonth('admission_date', now()->month)
            ->whereYear('admission_date', now()->year)
            ->count();

        // ── Staff Growth (new hires this term vs total) ───────────────
        $newStaffThisTerm = $currentTerm
            ? User::activeStaff($tenantId)->where('employment_started_at', '>=', $currentTerm->start_date)->count()
            : 0;

        // ── Trial / Subscription Status ───────────────────────────────
        // Under the pay-per-student model there's no time-limited trial —
        // "trial" here means the school hasn't paid yet and is on the free
        // tier (≤20 students). Still used to target platform broadcasts.
        $tenant = auth()->user()->tenant;
        $isOnTrial = \App\Services\PricingService::isFree(\App\Services\PricingService::activeStudentCount($tenantId));
        $trialDaysLeft = 0;

        // ── Platform Broadcasts ───────────────────────────────────────
        $broadcasts = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('platform_broadcasts')) {
                $tenantStatus = $tenant ? ($isOnTrial ? 'trial' : ($tenant->is_active ? 'active' : 'expired')) : 'active';
                $broadcasts = \Illuminate\Support\Facades\DB::table('platform_broadcasts')
                    ->whereIn('target', ['all', $tenantStatus])
                    ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
                    ->whereNotExists(fn($q) => $q->from('platform_broadcast_dismissals')
                        ->whereColumn('broadcast_id', 'platform_broadcasts.id')
                        ->where('tenant_id', $tenantId))
                    ->orderByDesc('created_at')
                    ->get();
            }
        } catch (\Exception $e) {}

        // ── Risk Flags ────────────────────────────────────────────────
        $openRiskFlags = null;
        try {
            $openRiskFlags = StudentRiskFlag::where('status', 'open')
                ->when($currentTerm, fn($q) => $q->where('term_id', $currentTerm->id))
                ->selectRaw("risk_level, COUNT(*) as count")
                ->groupBy('risk_level')
                ->pluck('count', 'risk_level');
        } catch (\Exception $e) {}

        // ── Pending Admissions ────────────────────────────────────────
        $pendingAdmissions = 0;
        try {
            $pendingAdmissions = Admission::where('status', 'pending')->count();
        } catch (\Exception $e) {}

        // ── Recent Announcements ──────────────────────────────────────
        $announcements = [];
        try {
            $announcements = Announcement::latest()->limit(3)->get();
        } catch (\Exception $e) {}

        // ── Gender breakdown ──────────────────────────────────────────
        $genderBreakdown = Student::where('status', Student::STATUS_ACTIVE)
            ->selectRaw("IFNULL(gender,'unknown') as gender, COUNT(*) as count")
            ->groupBy('gender')
            ->pluck('count','gender');

        return view('dashboard.index', compact(
            'currentTerm', 'totalStudents', 'totalStaff', 'totalClasses',
            'totalInvoiced', 'totalCollected', 'totalOutstanding', 'collectionRate',
            'presentToday', 'absentToday', 'attendanceRate',
            'attendanceTrend', 'feesTrend', 'studentsByClass',
            'openRiskFlags', 'pendingAdmissions', 'announcements', 'genderBreakdown',
            'isOnTrial', 'trialDaysLeft', 'broadcasts',
            'enrollmentGrowth', 'enrollmentGrowthPct', 'newAdmissionsThisMonth', 'newStaffThisTerm'
        ));
    }

    private function superDashboard()
    {
        $hasPlatformPayments = \Illuminate\Support\Facades\Schema::hasTable('platform_payments');

        $stats = [
            'tenants'            => \App\Models\Tenant::count(),
            'active'             => \App\Models\Tenant::where('status', 'active')->count(),
            'expired'            => \App\Models\Tenant::where('status', 'subscription_expired')->count(),
            'suspended'          => \App\Models\Tenant::where('status', 'suspended')->count(),
            'pending'            => \App\Models\Tenant::where('status', 'pending')->count(),
            'total_students'     => Student::withoutTenantScope()->count(),
            'total_users'        => User::whereNotNull('tenant_id')->count(),
            'revenue_this_month' => $hasPlatformPayments ? DB::table('platform_payments')
                                      ->where('status', 'confirmed')
                                      ->whereMonth('paid_at', now()->month)
                                      ->whereYear('paid_at', now()->year)
                                      ->sum('amount') : 0,
            'revenue_total'      => $hasPlatformPayments ? DB::table('platform_payments')
                                      ->where('status', 'confirmed')
                                      ->sum('amount') : 0,
            'expiring_soon'      => \App\Models\Tenant::where('subscription_expires_at', '<=', now()->addDays(14))
                                          ->where('subscription_expires_at', '>=', now())
                                          ->count(),
        ];

        $recentTenants = \App\Models\Tenant::query()
            ->latest()
            ->limit(8)
            ->get();

        $recentPayments = $hasPlatformPayments ? DB::table('platform_payments')
            ->join('tenants', 'tenants.id', '=', 'platform_payments.tenant_id')
            ->select('platform_payments.*', 'tenants.name as school_name')
            ->orderByDesc('platform_payments.created_at')
            ->limit(6)
            ->get() : collect();

        $expiringTenants = \App\Models\Tenant::where('subscription_expires_at', '<=', now()->addDays(14))
            ->where('subscription_expires_at', '>=', now())
            ->orderBy('subscription_expires_at')
            ->get();

        return view('super.dashboard', compact('stats', 'recentTenants', 'recentPayments', 'expiringTenants'));
    }
}
