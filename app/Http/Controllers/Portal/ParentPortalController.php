<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\GradingSystem;
use App\Models\Score;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\AttendanceRecord;
use App\Models\Invoice;
use App\Models\Announcement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * ParentPortalController
 *
 * Handles the parent-facing portal. Accessed by users with role = 'parent'.
 * A parent user is linked to a Guardian record via user_id.
 * The guardian has one or more students via guardian_student pivot.
 */
/**
 * Unified-portal parent section (URL space: /portal/parent/*).
 *
 * Renders the feature-rich parent experience (dashboard, results, fees, attendance,
 * notifications, calendar) inside the shared portal shell. Authentication is handled by
 * the portal guard, so this controller has NO login/logout of its own — that lives in the
 * standalone App\Http\Controllers\ParentPortalController. The two share a name by design,
 * disambiguated by namespace; they are not duplicates and must not be blindly consolidated.
 */
class ParentPortalController extends Controller
{
    private function getGuardian(): Guardian
    {
        $guardian = Guardian::where('user_id', Auth::id())->first();
        if (!$guardian) abort(403, 'No guardian profile linked to your account.');
        return $guardian;
    }

    private function resolveStudent(Request $request, Guardian $guardian): ?Student
    {
        $students = $guardian->students()->get();

        if (!$request->filled('student_id')) {
            return $students->first();
        }

        $student = $students->firstWhere('id', (int) $request->get('student_id'));
        abort_unless($student, 403, 'This student is not linked to your parent account.');

        return $student;
    }

    // ── Dashboard ─────────────────────────────────────────────────────
    public function dashboard(Request $request)
    {
        $guardian    = $this->getGuardian();
        $students    = $guardian->students()->with(['currentClassArm.classLevel'])->get();
        $student     = $this->resolveStudent($request, $guardian);
        $currentTerm = Term::where('is_current', true)->first();

        $summary    = null;
        $attendance = null;
        $outstanding = 0;

        if ($student) {
            $summary = null;
            if ($currentTerm && \Illuminate\Support\Facades\Schema::hasTable('termly_summaries')) {
                $summary = TermlySummary::where('student_id', $student->id)->where('term_id', $currentTerm->id)->first();
            }

            $attendance = $currentTerm
                ? AttendanceRecord::where('student_id', $student->id)
                    ->where('term_id', $currentTerm->id)
                    ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present")
                    ->first()
                : null;

            $outstanding = Invoice::where('student_id', $student->id)
                ->where('status', '!=', 'paid')
                ->selectRaw('SUM(total_amount - amount_paid) as bal')
                ->value('bal') ?? 0;
        }

        $announcements = Announcement::where('tenant_id', $guardian->tenant_id)
            ->where('is_published', true)
            ->whereIn('audience', ['all', 'parents'])
            ->latest('publish_date')->limit(6)->get();

        $calendar = \Illuminate\Support\Facades\Schema::hasTable('calendar_events')
            ? \App\Models\CalendarEvent::where('tenant_id', $guardian->tenant_id)
                ->where('start_date', '>=', now()->toDateString())
                ->orderBy('start_date')->limit(5)->get()
            : collect();

        return view('portal.parent.dashboard', compact(
            'guardian', 'students', 'student',
            'currentTerm', 'summary', 'attendance', 'outstanding',
            'announcements', 'calendar'
        ));
    }

    // ── Results ───────────────────────────────────────────────────────
    public function results(Request $request)
    {
        $guardian = $this->getGuardian();
        $students = $guardian->students()->get();
        $student  = $this->resolveStudent($request, $guardian);

        $terms  = Term::with('session')->latest()->get();
        // Default to current term, fall back to most recent term if none is marked current
        $termId = $request->get('term_id',
            optional($terms->firstWhere('is_current', true))->id
            ?? optional($terms->first())->id
        );

        $summary = null;
        $assessmentTypes = collect();
        $rawScoresBySubject = [];
        if ($student && $termId && \Illuminate\Support\Facades\Schema::hasTable('termly_summaries')) {
            $summary = TermlySummary::where('student_id', $student->id)
                ->where('term_id', $termId)
                ->first();
        }
        if ($student && $termId) {
            $assessmentTypes = \Illuminate\Support\Facades\Schema::hasTable('assessment_types')
                ? AssessmentType::where('term_id', $termId)->orderBy('is_exam')->orderBy('name')->get()
                : collect();
            $rawScores = Score::where('student_id', $student->id)->where('term_id', $termId)->get();
            foreach ($rawScores as $sc) {
                $rawScoresBySubject[$sc->subject_id][$sc->assessment_type_id] = $sc->score;
            }
        }

        return view('portal.parent.results', compact('guardian', 'students', 'student', 'terms', 'termId', 'summary', 'assessmentTypes', 'rawScoresBySubject'));
    }

    // ── Fees ──────────────────────────────────────────────────────────
    public function fees(Request $request)
    {
        $guardian = $this->getGuardian();
        $students = $guardian->students()->get();
        $student  = $this->resolveStudent($request, $guardian);

        $invoices = $student
            ? Invoice::where('student_id', $student->id)->latest()->paginate(15)
            : collect();

        $totals = $student
            ? Invoice::where('student_id', $student->id)
                ->selectRaw('SUM(total_amount) as billed, SUM(amount_paid) as paid, SUM(total_amount-amount_paid) as outstanding')
                ->first()
            : null;

        $gatewayActive = \Illuminate\Support\Facades\Schema::hasTable('payment_gateway_configs')
            && \App\Models\PaymentGatewayConfig::where('tenant_id', $guardian->tenant_id)->where('is_active', true)->exists();

        // Load installments for all fetched invoices keyed by invoice_id
        $installments = $student && $invoices->count() && \Illuminate\Support\Facades\Schema::hasTable('fee_installments')
            ? \App\Models\FeeInstallment::whereIn('invoice_id', $invoices->pluck('id'))
                ->orderBy('installment_number')
                ->get()
                ->groupBy('invoice_id')
            : collect();

        return view('portal.parent.fees', compact(
            'guardian', 'students', 'student', 'invoices', 'totals', 'gatewayActive', 'installments'
        ));
    }

    // ── Attendance ────────────────────────────────────────────────────
    public function attendance(Request $request)
    {
        $guardian    = $this->getGuardian();
        $students    = $guardian->students()->get();
        $student     = $this->resolveStudent($request, $guardian);
        $currentTerm = Term::where('is_current', true)->first();
        $termId      = $request->get('term_id', $currentTerm?->id);
        $terms       = Term::with('session')->latest()->get();

        $records = $student
            ? AttendanceRecord::where('student_id', $student->id)
                ->when($termId, fn($q) => $q->where('term_id', $termId))
                ->orderByDesc('attendance_date')->get()
            : collect();

        $stats = [
            'total'   => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'absent'  => $records->where('status', 'absent')->count(),
            'late'    => $records->where('status', 'late')->count(),
        ];
        $stats['rate'] = $stats['total'] > 0
            ? round(($stats['present'] / $stats['total']) * 100, 1) : 0;

        return view('portal.parent.attendance', compact(
            'guardian', 'students', 'student', 'records', 'stats', 'terms', 'termId'
        ));
    }

    // ── Notifications ──────────────────────────────────────────────────
    public function notifications(Request $request)
    {
        $guardian = $this->getGuardian();

        $announcements = Announcement::where('tenant_id', $guardian->tenant_id)
            ->where('is_published', true)
            ->whereIn('audience', ['all', 'parents'])
            ->latest('publish_date')->paginate(20);

        $calendar = \Illuminate\Support\Facades\Schema::hasTable('calendar_events')
            ? \App\Models\CalendarEvent::where('tenant_id', $guardian->tenant_id)
                ->where('start_date', '>=', now()->subDays(7)->toDateString())
                ->orderBy('start_date')->get()
            : collect();

        return view('portal.parent.notifications', compact('guardian', 'announcements', 'calendar'));
    }

    // ── Calendar ──────────────────────────────────────────────────────
    public function calendar()
    {
        $guardian = $this->getGuardian();

        $events = \Illuminate\Support\Facades\Schema::hasTable('calendar_events')
            ? \App\Models\CalendarEvent::where('tenant_id', $guardian->tenant_id)
                ->orderBy('start_date')->get()
            : collect();

        return view('portal.parent.calendar', compact('guardian', 'events'));
    }

    // ── Fee Payment Initiation ────────────────────────────────────────
    public function payFee(Request $request, Invoice $invoice)
    {
        $guardian = $this->getGuardian();

        $studentIds = $guardian->students()->pluck('students.id');
        abort_unless($studentIds->contains($invoice->student_id), 403, 'You cannot pay this invoice.');
        abort_unless($invoice->tenant_id === $guardian->tenant_id, 403);

        $balance = $invoice->total_amount - $invoice->amount_paid;
        if ($balance <= 0) {
            return back()->withErrors(['error' => 'This invoice is already fully paid.']);
        }

        $config = \App\Models\PaymentGatewayConfig::where('tenant_id', $guardian->tenant_id)
            ->where('is_active', true)->first();
        if (!$config) {
            return back()->withErrors(['error' => 'Online payment is not yet configured for this school. Please contact the school office.']);
        }

        $reference = 'SMS-' . strtoupper(Str::random(12));
        $email     = Auth::user()->email ?? $guardian->email ?? 'parent@school.ng';

        if (\Illuminate\Support\Facades\Schema::hasTable('online_payment_logs')) {
            \App\Models\OnlinePaymentLog::create([
                'invoice_id'  => $invoice->id,
                'student_id'  => $invoice->student_id,
                'gateway'     => $config->gateway,
                'reference'   => $reference,
                'amount'      => $balance,
                'status'      => 'pending',
            ]);
        }

        if ($config->gateway === 'paystack') {
            return view('fees.pay-paystack', compact('invoice', 'config', 'reference', 'balance', 'email'));
        }
        if ($config->gateway === 'flutterwave') {
            return view('fees.pay-flutterwave', compact('invoice', 'config', 'reference', 'balance', 'email'));
        }

        // Monnify: get access token then redirect to hosted checkout
        $base  = $config->is_live ? 'https://api.monnify.com' : 'https://sandbox.monnify.com';
        $auth  = \Illuminate\Support\Facades\Http::withBasicAuth($config->public_key, $config->secret_key)
                     ->post("{$base}/api/v1/auth/login");
        $token = $auth->successful() ? $auth->json('responseBody.accessToken') : null;
        if (!$token) {
            return back()->withErrors(['error' => 'Could not connect to Monnify. Please try again later.']);
        }
        $init = \Illuminate\Support\Facades\Http::withToken($token)->post("{$base}/api/v1/merchant/transactions/init-transaction", [
            'amount'             => $balance,
            'customerName'       => $invoice->student?->full_name ?? 'Parent',
            'customerEmail'      => $email,
            'paymentReference'   => $reference,
            'paymentDescription' => 'School Fees — ' . ($invoice->student?->full_name ?? ''),
            'currencyCode'       => 'NGN',
            'contractCode'       => $config->contract_code,
            'redirectUrl'        => route('fees.gateway.monnify.callback'),
            'paymentMethods'     => ['CARD', 'ACCOUNT_TRANSFER'],
        ]);
        $checkoutUrl = $init->successful() ? $init->json('responseBody.checkoutUrl') : null;
        if (!$checkoutUrl) {
            return back()->withErrors(['error' => 'Could not start Monnify checkout. Please try again.']);
        }
        return redirect()->away($checkoutUrl);
    }

    // ── Report Card PDF ───────────────────────────────────────────────
    public function reportCardPdf(Request $request)
    {
        ini_set('memory_limit', '256M');

        $guardian = $this->getGuardian();
        $student  = $this->resolveStudent($request, $guardian);
        abort_unless($student, 404, 'No student selected.');

        if (!$request->filled('term_id')) {
            return back()->withErrors(['error' => 'Please select a term to download the report card.']);
        }
        $term = Term::with('session')->where('id', $request->term_id)->first();
        if (!$term) {
            return back()->withErrors(['error' => 'The selected term was not found.']);
        }
        abort_unless($term->tenant_id === $guardian->tenant_id, 403);

        $summary = TermlySummary::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->first();

        if (!$summary) {
            return back()->withErrors(['error' => 'Report card not yet published for this term. Please check back later.']);
        }

        abort_unless($student->current_class_arm_id, 404, 'Student has no class assigned.');
        $classArm = ClassArm::with('classLevel', 'formTutor')->find($student->current_class_arm_id);
        if (!$classArm) {
            return back()->withErrors(['error' => 'Student class record not found. Please contact the school office.']);
        }
        $session  = $term->session;
        $tenant   = Auth::user()->tenant;

        $termName    = strtolower($term->name);
        $isThirdTerm = str_contains($termName, '3rd') || str_contains($termName, 'third');
        $orientation = $isThirdTerm ? 'landscape' : 'portrait';

        $assessmentTypes = \Illuminate\Support\Facades\Schema::hasTable('assessment_types')
            ? AssessmentType::where('term_id', $term->id)->orderBy('is_exam')->orderBy('name')->get()
            : collect();

        $rawScores     = Score::where('student_id', $student->id)->where('term_id', $term->id)->get();
        $subjects      = Subject::whereIn('id', $rawScores->pluck('subject_id')->unique())->orderBy('name')->get();
        $gradingSystem = GradingSystem::where('class_level_id', $classArm->class_level_id)->get();

        $classmateIds = Student::where('current_class_arm_id', $classArm->id)->where('status', Student::STATUS_ACTIVE)->pluck('id');
        $classScores  = Score::whereIn('student_id', $classmateIds)->where('term_id', $term->id)->get();

        $subjectRows = [];
        foreach ($subjects as $subject) {
            $subScores   = $rawScores->where('subject_id', $subject->id);
            $total       = round($subScores->sum('score'), 1);
            $grade       = $gradingSystem->filter(fn ($g) => $total >= $g->min_score && $total <= $g->max_score)->first();
            // Fallback grade when no grading system configured
            if (!$grade && $gradingSystem->isEmpty()) {
                $gradeLetter = match(true) { $total>=70=>'A', $total>=60=>'B', $total>=50=>'C', $total>=45=>'D', $total>=40=>'E', default=>'F' };
                $gradeRemark = match(true) { $total>=70=>'Excellent', $total>=60=>'Very Good', $total>=50=>'Good', $total>=40=>'Pass', default=>'Fail' };
                $gradePass   = $total >= 40;
            } else {
                $gradeLetter = $grade?->grade_letter ?? '—';
                $gradeRemark = $grade?->remark ?? '—';
                $gradePass   = $grade?->is_pass_grade ?? false;
            }
            $scoresKeyed = [];
            foreach ($subScores as $s) {
                $scoresKeyed[$s->assessment_type_id] = $s->score;
            }
            $classTotals = [];
            foreach ($classmateIds as $cid) {
                $t = $classScores->where('student_id', $cid)->where('subject_id', $subject->id)->sum('score');
                if ($t > 0) $classTotals[] = $t;
            }
            $classAvg = count($classTotals) ? round(array_sum($classTotals) / count($classTotals), 2) : null;
            $subjectPosition = '—';
            if (count($classTotals) > 0) {
                $sorted = $classTotals; arsort($sorted);
                $rank = array_search($total, array_values($sorted));
                $subjectPosition = $rank !== false ? $rank + 1 : '—';
            }
            $row = [
                'subject_name'   => $subject->name,
                'scores'         => $scoresKeyed,
                'total'          => $total,
                'grade'          => $gradeLetter,
                'remark'         => $gradeRemark,
                'is_pass'        => $gradePass,
                'class_highest'  => $classTotals ? round(max($classTotals), 1) : '—',
                'class_lowest'   => $classTotals ? round(min($classTotals), 1) : '—',
                'class_avg'      => $classAvg ? number_format($classAvg, 2) : '—',
                'class_position' => $subjectPosition,
                'class_count'    => count($classmateIds),
            ];
            if ($isThirdTerm) {
                $allTerms = Term::where('session_id', $term->session_id)->orderBy('start_date')->get();
                foreach ($allTerms as $i => $t2) {
                    $tScores = Score::where('student_id', $student->id)->where('subject_id', $subject->id)->where('term_id', $t2->id)->get();
                    $row['term' . ($i + 1) . '_avg'] = round($tScores->sum('score'), 1);
                }
                $annualTotal = ($row['term1_avg'] ?? 0) + ($row['term2_avg'] ?? 0) + ($row['term3_avg'] ?? 0);
                $row['annual_total']   = round($annualTotal, 1);
                $row['cumulative_avg'] = round($annualTotal / 3, 1);
                $annualGrade  = $gradingSystem->filter(fn($g) => $row['cumulative_avg'] >= $g->min_score && $row['cumulative_avg'] <= $g->max_score)->first();
                $row['grade']  = $annualGrade?->grade_letter ?? ($row['cumulative_avg'] >= 40 ? 'Pass' : 'Fail');
                $row['remark'] = $annualGrade?->remark ?? '—';
                $row['is_pass']= $annualGrade?->is_pass_grade ?? ($row['cumulative_avg'] >= 40);
            }
            $subjectRows[] = $row;
        }

        $psychomotorSkills = \Illuminate\Support\Facades\Schema::hasTable('skill_definitions')
            ? \App\Models\SkillDefinition::psychomotor()->where('tenant_id', $tenant->id)->get()
            : collect();
        $affectiveSkills = \Illuminate\Support\Facades\Schema::hasTable('skill_definitions')
            ? \App\Models\SkillDefinition::affective()->where('tenant_id', $tenant->id)->get()
            : collect();
        $skillRatings = \Illuminate\Support\Facades\Schema::hasTable('student_skill_ratings')
            ? \App\Models\StudentSkillRating::where('student_id', $student->id)->where('term_id', $term->id)->get()
            : collect();

        try {
            $pdf = Pdf::loadView('reports.pdf', compact(
                'student', 'classArm', 'term', 'session', 'tenant',
                'summary', 'isThirdTerm', 'assessmentTypes', 'subjectRows',
                'gradingSystem', 'psychomotorSkills', 'affectiveSkills', 'skillRatings'
            ))->setPaper('a4', $orientation);

            $filename = 'ReportCard_' . str_replace(' ', '_', $student->full_name) . '_' . str_replace(' ', '_', $term->name) . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Report card PDF failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Report card could not be generated. Please try again or contact support.']);
        }
    }
}
