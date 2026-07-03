<?php
namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\Student;
use App\Models\Score;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\AttendanceRecord;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $session = AcademicSession::where('is_current', true)->first();
        $terms   = Term::when($session, fn($q) => $q->where('session_id', $session->id))->get();

        // Overall school performance
        $classPerformance = TermlySummary::when($terms->count(), fn($q) => $q->whereIn('term_id', $terms->pluck('id')))
            ->join('class_arms','class_arms.id','=','termly_summaries.class_arm_id')
            ->join('class_levels','class_levels.id','=','class_arms.class_level_id')
            ->select(
                'class_levels.name as level',
                DB::raw('CONCAT(class_levels.name," ",class_arms.name) as class_name'),
                DB::raw('AVG(final_average) as avg_score'),
                DB::raw('COUNT(*) as student_count'),
                DB::raw('SUM(CASE WHEN subjects_failed=0 THEN 1 ELSE 0 END) as passed_all')
            )
            ->groupBy('class_arms.id','class_levels.name','class_arms.name')
            ->orderByDesc('avg_score')
            ->get();

        // Subject performance
        $subjectPerformance = Score::when($terms->count(), fn($q) => $q->whereIn('term_id', $terms->pluck('id')))
            ->join('subjects','subjects.id','=','scores.subject_id')
            ->select(
                'subjects.name as subject',
                DB::raw('AVG(score) as avg_score'),
                DB::raw('MIN(score) as min_score'),
                DB::raw('MAX(score) as max_score'),
                DB::raw('COUNT(*) as attempts')
            )
            ->groupBy('subjects.id','subjects.name')
            ->orderByDesc('avg_score')->get();

        // Monthly enrollment trend
        $enrollmentTrend = Student::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('COUNT(*) as count')
            )
            ->whereYear('created_at', date('Y'))
            ->groupBy('year','month')
            ->orderBy('year')->orderBy('month')
            ->get();

        // Fee collection
        $feeCollection = Invoice::select(
                DB::raw('SUM(total_amount) as billed'),
                DB::raw('SUM(amount_paid) as collected'),
                DB::raw('SUM(total_amount - amount_paid) as outstanding')
            )->when($session, fn($q) => $q->where('session_id', $session->id))
            ->first();

        // Attendance overview
        $attendanceRate = AttendanceRecord::when($terms->count(), fn($q) => $q->whereIn('term_id', $terms->pluck('id')))
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present")
            )->first();

        return view('analytics.index', compact(
            'session','classPerformance','subjectPerformance',
            'enrollmentTrend','feeCollection','attendanceRate'
        ));
    }

    public function classReport(Request $request)
    {
        $classArms = ClassArm::with('classLevel')->get();
        $terms     = Term::with('session')->latest()->get();

        if (!$request->filled('class_arm_id') || !$request->filled('term_id')) {
            return view('analytics.class-report', compact('classArms','terms'));
        }

        $classArm  = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $term      = Term::with('session')->findOrFail($request->term_id);

        $summaries = TermlySummary::where('class_arm_id',$classArm->id)
            ->where('term_id',$term->id)
            ->with('student')
            ->orderBy('position_in_class')
            ->get();

        return view('analytics.class-report', compact(
            'classArms','terms','classArm','term','summaries'
        ));
    }

    public function subjectAnalysis(Request $request)
    {
        $terms    = Term::with('session')->latest()->get();
        $subjects = \App\Models\Subject::orderBy('name')->get();

        $analysis = null;
        if ($request->filled('term_id')) {
            $analysis = Score::where('term_id', $request->term_id)
                ->join('subjects','subjects.id','=','scores.subject_id')
                ->join('students','students.id','=','scores.student_id')
                ->select(
                    'subjects.name as subject',
                    DB::raw('AVG(score) as avg'),
                    DB::raw('MIN(score) as min'),
                    DB::raw('MAX(score) as max'),
                    DB::raw('COUNT(DISTINCT scores.student_id) as students'),
                    DB::raw('SUM(CASE WHEN score < 40 THEN 1 ELSE 0 END) as failing')
                )
                ->groupBy('subjects.id','subjects.name')
                ->orderByDesc('avg')
                ->get();
        }

        return view('analytics.subject-analysis', compact('terms','subjects','analysis'));
    }

    // ── Teacher Performance Report ───────────────────────────────
    public function teacherReport()
    {
        $classArms = \App\Models\ClassArm::with(['classLevel','formTutor'])->get();
        $terms     = \App\Models\Term::with('session')->latest()->get();

        $teacherStats = \App\Models\User::activeStaff(auth()->user()->tenant_id)
            ->whereIn('role', array_merge(
                \App\Models\User::teachingRoleNames(),
                ['principal', 'vice_principal']
            ))
            ->get()->map(function($teacher) {
                $arms   = \App\Models\ClassArm::where('form_tutor_id', $teacher->id)->get();
                $armIds = $arms->pluck('id');
                $avg    = \App\Models\TermlySummary::whereIn('class_arm_id', $armIds)->avg('final_average');
                return [
                    'teacher'   => $teacher,
                    'classes'   => $arms->count(),
                    'avg_score' => round($avg ?? 0, 1),
                    'students'  => \App\Models\Student::whereIn('current_class_arm_id', $armIds)
                        ->where('status', Student::STATUS_ACTIVE)
                        ->count(),
                ];
            })->sortByDesc('avg_score');

        return view('analytics.teachers', compact('teacherStats', 'classArms', 'terms'));
    }

    // ── Financial Report ──────────────────────────────────────────
    public function financial()
    {
        $tenantId  = auth()->user()?->tenant_id;
        $sessions  = \App\Models\AcademicSession::latest()->get();
        $sessionId = request('session_id', optional($sessions->first())->id);
        $termId    = request('term_id');
        $terms     = \App\Models\Term::where('session_id', $sessionId)->with('session')->orderBy('start_date')->get();
        $selectedTerm = $termId ? $terms->firstWhere('id', (int) $termId) : null;
        $reportStart  = $selectedTerm?->start_date ?? optional($terms->sortBy('start_date')->first())->start_date;
        $reportEnd    = $selectedTerm?->end_date ?? optional($terms->sortByDesc('end_date')->first())->end_date;

        // ── Fee summary ────────────────────────────────────────────
        $feeQuery = \App\Models\Invoice::when($sessionId, fn($q) => $q->where('session_id', $sessionId))
                        ->when($termId, fn($q) => $q->where('term_id', $termId));

        $feeData = (clone $feeQuery)
            ->selectRaw('
                COUNT(*) as invoice_count,
                SUM(total_amount) as billed,
                SUM(amount_paid) as collected,
                SUM(CASE WHEN status="waived" THEN 0 ELSE GREATEST(total_amount - amount_paid, 0) END) as outstanding,
                SUM(discount_amount) as generation_discount_total,
                SUM(CASE WHEN status="waived" THEN GREATEST(total_amount - amount_paid, 0) ELSE 0 END) as waived_amount,
                SUM(CASE WHEN status="paid" THEN 1 ELSE 0 END) as fully_paid_count,
                SUM(CASE WHEN status="partially_paid" THEN 1 ELSE 0 END) as partial_count,
                SUM(CASE WHEN status="unpaid" OR (status!="waived" AND amount_paid=0) THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN status="waived" THEN 1 ELSE 0 END) as waived_count
            ')->first();

        // ── Monthly collection trend ───────────────────────────────
        $monthlyCollection = \Illuminate\Support\Facades\DB::table('payment_transactions')
            ->join('invoices','invoices.id','=','payment_transactions.invoice_id')
            ->where('payment_transactions.status', 'success')
            ->whereNull('invoices.deleted_at')
            ->when($tenantId, fn($q) => $q
                ->where('payment_transactions.tenant_id', $tenantId)
                ->where('invoices.tenant_id', $tenantId))
            ->when($sessionId, fn($q) => $q->where('invoices.session_id', $sessionId))
            ->when($termId, fn($q) => $q->where('invoices.term_id', $termId))
            ->selectRaw('DATE_FORMAT(COALESCE(payment_transactions.paid_at, payment_transactions.created_at), "%Y-%m") as month, SUM(payment_transactions.amount_paid) as amount')
            ->groupBy('month')->orderBy('month')
            ->get()
            ->keyBy('month');

        // Legacy fallback for older data created before payment_transactions was populated.
        if ($monthlyCollection->isEmpty()) {
            $monthlyCollection = (clone $feeQuery)
                ->where('amount_paid', '>', 0)
                ->selectRaw('DATE_FORMAT(updated_at, "%Y-%m") as month, SUM(amount_paid) as amount')
                ->groupBy('month')->orderBy('month')
                ->get()->keyBy('month');
        }

        // ── By class breakdown ─────────────────────────────────────
        $byClass = (clone $feeQuery)
            ->join('students','students.id','=','invoices.student_id')
            ->join('class_arms','class_arms.id','=','students.current_class_arm_id')
            ->join('class_levels','class_levels.id','=','class_arms.class_level_id')
            ->selectRaw('
                CONCAT(class_levels.name," ",class_arms.name) as class_name,
                COUNT(*) as invoices,
                SUM(invoices.total_amount) as billed,
                SUM(invoices.amount_paid) as collected,
                SUM(CASE WHEN invoices.status="waived" THEN 0 ELSE GREATEST(invoices.total_amount - invoices.amount_paid, 0) END) as outstanding
            ')
            ->groupBy('class_arms.id','class_levels.name','class_arms.name')
            ->orderBy('class_levels.name')
            ->get();

        // ── By fee category breakdown ──────────────────────────────
        $byCategory = \Illuminate\Support\Facades\DB::table('invoice_items')
            ->join('invoices','invoices.id','=','invoice_items.invoice_id')
            ->join('fee_categories','fee_categories.id','=','invoice_items.fee_category_id')
            ->when($tenantId, fn($q) => $q
                ->where('invoice_items.tenant_id', $tenantId)
                ->where('invoices.tenant_id', $tenantId)
                ->where('fee_categories.tenant_id', $tenantId))
            ->when($sessionId, fn($q) => $q->where('invoices.session_id', $sessionId))
            ->when($termId, fn($q) => $q->where('invoices.term_id', $termId))
            ->selectRaw('fee_categories.name as category, SUM(invoice_items.amount) as total')
            ->groupBy('fee_categories.id','fee_categories.name')
            ->orderByDesc('total')
            ->get();

        // ── Expenses ───────────────────────────────────────────────
        // Revenue deductions and adjustments.
        $generationDiscountTotal = (float) ($feeData->generation_discount_total ?? 0);
        $approvedDiscountTotal = \Illuminate\Support\Facades\DB::table('invoice_discounts')
            ->join('invoices','invoices.id','=','invoice_discounts.invoice_id')
            ->whereNull('invoices.deleted_at')
            ->when($tenantId, fn($q) => $q
                ->where('invoice_discounts.tenant_id', $tenantId)
                ->where('invoices.tenant_id', $tenantId))
            ->when($sessionId, fn($q) => $q->where('invoices.session_id', $sessionId))
            ->when($termId, fn($q) => $q->where('invoices.term_id', $termId))
            ->sum('invoice_discounts.amount');

        $waivedInvoiceTotal = (float) ($feeData->waived_amount ?? 0);
        $waivedInvoiceCount = (int) ($feeData->waived_count ?? 0);

        $reversedPaymentTotal = \Illuminate\Support\Facades\DB::table('payment_transactions')
            ->join('invoices','invoices.id','=','payment_transactions.invoice_id')
            ->where('payment_transactions.status', 'reversed')
            ->whereNull('invoices.deleted_at')
            ->when($tenantId, fn($q) => $q
                ->where('payment_transactions.tenant_id', $tenantId)
                ->where('invoices.tenant_id', $tenantId))
            ->when($sessionId, fn($q) => $q->where('invoices.session_id', $sessionId))
            ->when($termId, fn($q) => $q->where('invoices.term_id', $termId))
            ->sum('payment_transactions.amount_paid');

        $totalRevenueDeductions = $generationDiscountTotal
            + $approvedDiscountTotal
            + $waivedInvoiceTotal
            + $reversedPaymentTotal;

        $expenseQuery = \App\Models\SchoolExpense::query()
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->when($termId, fn($q) => $q->where('term_id', $termId));
        $expenses     = $expenseQuery->selectRaw('category, SUM(amount) as total')->groupBy('category')->get();
        $totalExp     = $expenses->sum('total');

        // ── Payroll cost ───────────────────────────────────────────
        $payrollTotals = \Illuminate\Support\Facades\DB::table('payroll_items')
            ->join('payroll_periods','payroll_periods.id','=','payroll_items.payroll_period_id')
            ->when($tenantId, fn($q) => $q
                ->where('payroll_items.tenant_id', $tenantId)
                ->where('payroll_periods.tenant_id', $tenantId))
            ->when($reportStart && $reportEnd, function ($q) use ($reportStart, $reportEnd) {
                $q->whereDate('payroll_periods.period_start', '<=', $reportEnd->toDateString())
                  ->whereDate('payroll_periods.period_end', '>=', $reportStart->toDateString());
            })
            ->where('payroll_items.payment_status', 'paid')
            ->selectRaw('
                SUM(payroll_items.net_pay) as net_pay,
                SUM(payroll_items.tax_deduction) as tax_deduction,
                SUM(payroll_items.pension_deduction) as pension_deduction,
                SUM(payroll_items.other_deductions) as other_deductions,
                SUM(payroll_items.total_deductions) as total_deductions
            ')
            ->first();

        $payrollCost = (float) ($payrollTotals->net_pay ?? 0);
        $payrollDeductions = [
            'tax'     => (float) ($payrollTotals->tax_deduction ?? 0),
            'pension' => (float) ($payrollTotals->pension_deduction ?? 0),
            'other'   => (float) ($payrollTotals->other_deductions ?? 0),
            'total'   => (float) ($payrollTotals->total_deductions ?? 0),
        ];

        // ── Net balance ────────────────────────────────────────────
        $totalCollected = (float) ($feeData->collected ?? 0);
        $netFeeCollections = $totalCollected - $reversedPaymentTotal;
        $netBalance     = $netFeeCollections - $totalExp - $payrollCost;

        // ── Collection rate ────────────────────────────────────────
        $collectableBilled = max((float) ($feeData->billed ?? 0) - $waivedInvoiceTotal, 0);
        $collectionRate = $collectableBilled > 0
            ? round((max($netFeeCollections, 0) / $collectableBilled) * 100, 1)
            : 0;

        return view('analytics.financial', compact(
            'sessions','sessionId','terms','termId',
            'feeData','byClass','byCategory',
            'expenses','totalExp','payrollCost','netBalance',
            'monthlyCollection','collectionRate',
            'generationDiscountTotal','approvedDiscountTotal',
            'waivedInvoiceTotal','waivedInvoiceCount',
            'reversedPaymentTotal','totalRevenueDeductions',
            'netFeeCollections','payrollDeductions','collectableBilled'
        ));
    }

    // ── Comparative Term Report ───────────────────────────────────
    public function comparative(\Illuminate\Http\Request $request)
    {
        $classArms = \App\Models\ClassArm::with('classLevel')->get();
        $sessions  = \App\Models\AcademicSession::with('terms')->latest()->get();

        $data = null;
        if ($request->filled('class_arm_id')) {
            $classArm = \App\Models\ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
            $data = \App\Models\TermlySummary::where('class_arm_id', $classArm->id)
                ->join('terms','terms.id','=','termly_summaries.term_id')
                ->join('academic_sessions','academic_sessions.id','=','terms.session_id')
                ->selectRaw('terms.name as term_name, academic_sessions.name as session_name, AVG(final_average) as avg, COUNT(*) as students, MAX(final_average) as highest, MIN(final_average) as lowest')
                ->groupBy('terms.id','terms.name','academic_sessions.name')
                ->orderBy('academic_sessions.start_date')
                ->orderBy('terms.id')
                ->get();
        }

        return view('analytics.comparative', compact('classArms', 'sessions', 'data'));
    }

    // ── Learning Outcomes Tracker ─────────────────────────────────
    public function outcomes(\Illuminate\Http\Request $request)
    {
        $students = null;
        $classArms= \App\Models\ClassArm::with('classLevel')->get();
        $sessions = \App\Models\AcademicSession::with('terms')->latest()->get();

        if ($request->filled('class_arm_id')) {
            $classArm = \App\Models\ClassArm::findOrFail($request->class_arm_id);
            $students = \App\Models\Student::where('current_class_arm_id', $classArm->id)
                ->where('status', Student::STATUS_ACTIVE)
                ->with(['termSummaries' => fn($q) => $q->with('term.session')->orderBy('term_id')])
                ->orderBy('last_name')->get()
                ->map(function($student) {
                    $summaries = $student->termSummaries;
                    $trend = $summaries->pluck('final_average')->toArray();
                    $improving = count($trend) >= 2 && last($trend) > $trend[0];
                    return [
                        'student'   => $student,
                        'summaries' => $summaries,
                        'trend'     => $trend,
                        'improving' => $improving,
                        'latest_avg'=> last($trend) ?? 0,
                    ];
                })->sortByDesc('latest_avg');
        }

        return view('analytics.outcomes', compact('classArms','sessions','students'));
    }

}
