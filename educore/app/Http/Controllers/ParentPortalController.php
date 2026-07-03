<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Invoice;
use App\Models\TermlySummary;
use App\Models\AttendanceRecord;
use App\Models\Announcement;
use App\Models\ParentPortalAccount;
use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Standalone parent portal (URL space: /parent/*).
 *
 * This is the AUTH-OWNING parent controller: it provides parent login, logout and
 * one-time account setup, plus the legacy standalone dashboard/results/fees/messages
 * screens. It is intentionally distinct from App\Http\Controllers\Portal\ParentPortalController,
 * which renders the richer parent section of the unified portal (/portal/parent/*) but
 * delegates authentication and therefore has no login of its own. Do not merge the two
 * without first deciding which parent portal is canonical — this class holds the only
 * parent login/setup flow, so removing it would lock parents out.
 */
class ParentPortalController extends Controller
{
    private function sessionTenantId(): int
    {
        $tenantId = session('parent_tenant_id');
        if (!$tenantId) abort(redirect()->route('portal.parent.login'));

        return (int) $tenantId;
    }

    // ── Auth ─────────────────────────────────────────────────────────
    public function loginForm()
    {
        return view('parent.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        $account = ParentPortalAccount::where('email', $data['email'])
                    ->where('is_active', true)->first();

        if (!$account || !Hash::check($data['password'], $account->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        $account->update(['last_login' => now()]);
        session(['parent_portal_id' => $account->id, 'parent_tenant_id' => $account->tenant_id]);

        return redirect()->route('portal.parent.dashboard');
    }

    public function logout()
    {
        session()->forget(['parent_portal_id','parent_tenant_id']);
        return redirect()->route('portal.parent.login');
    }

    private function getAccount()
    {
        $id = session('parent_portal_id');
        if (!$id) abort(redirect()->route('portal.parent.login'));
        $tenantId = $this->sessionTenantId();

        return ParentPortalAccount::with([
                'guardian' => fn ($query) => $query->where('tenant_id', $tenantId),
                'guardian.students' => fn ($query) => $query->where('students.tenant_id', $tenantId),
            ])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);
    }

    private function studentsForAccount(ParentPortalAccount $account)
    {
        if (!$account->guardian) {
            return collect();
        }

        return $account->guardian->students()
            ->where('students.tenant_id', $account->tenant_id)
            ->get();
    }

    private function resolveStudent(Request $request, $students): ?Student
    {
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
        $account  = $this->getAccount();
        $guardian = $account->guardian;
        $students = $this->studentsForAccount($account);
        if ($students->isNotEmpty()) {
            $students->load(['currentClassArm.classLevel']);
        }
        $student  = $this->resolveStudent($request, $students);

        $currentTerm = \App\Models\Term::where('tenant_id', $account->tenant_id)
            ->where('is_current', true)
            ->first();
        $summary = $attendance = null;

        if ($student && $currentTerm) {
            $summary = TermlySummary::where('student_id',$student->id)->where('term_id',$currentTerm->id)->first();
            $attendance = AttendanceRecord::where('student_id',$student->id)->where('term_id',$currentTerm->id)
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present")->first();
        }

        $announcements = Announcement::where('is_published',true)
            ->where('tenant_id', $account->tenant_id)
            ->whereIn('audience',['all','parents','students'])
            ->where(fn($q)=>$q->whereNull('expire_date')->orWhere('expire_date','>=',today()))
            ->latest('publish_date')->limit(5)->get();

        $outstandingFees = $student
            ? $student->invoices->where('status','!=','paid')->sum(fn($i)=>$i->total_amount-$i->amount_paid)
            : 0;

        return view('parent.dashboard', compact(
            'account','guardian','students','student','summary','attendance','announcements','outstandingFees','currentTerm'
        ));
    }

    // ── Results ───────────────────────────────────────────────────────
    public function results(Request $request)
    {
        $account  = $this->getAccount();
        $guardian = $account->guardian;
        $students = $this->studentsForAccount($account);
        $student  = $this->resolveStudent($request, $students);
        $terms    = \App\Models\Term::where('tenant_id', $account->tenant_id)->with('session')->latest()->get();
        $termId   = $request->get('term_id', optional($terms->firstWhere('is_current',true))->id ?? optional($terms->first())->id);
        $summary  = $termId && $student ? TermlySummary::where('student_id',$student->id)->where('term_id',$termId)->first() : null;
        return view('parent.results', compact('account','students','student','terms','summary','termId'));
    }

    // ── Fees ──────────────────────────────────────────────────────────
    public function fees(Request $request)
    {
        $account  = $this->getAccount();
        $guardian = $account->guardian;
        $students = $this->studentsForAccount($account);
        $student  = $this->resolveStudent($request, $students);
        $invoices = $student ? $student->invoices()->latest()->paginate(10) : collect();
        return view('parent.fees', compact('account','students','student','invoices'));
    }

    // ── Messages ──────────────────────────────────────────────────────
    public function messages(Request $request)
    {
        $account  = $this->getAccount();
        $guardian = $account->guardian;
        $students = $this->studentsForAccount($account);
        $studentIds = $students->pluck('id');

        $threads = $studentIds->isNotEmpty()
            ? \App\Models\MessageThread::where('tenant_id', $account->tenant_id)
                ->whereIn('student_id', $studentIds)
                ->with(['student', 'initiator', 'replies.sender'])
                ->latest()
                ->get()
            : collect();

        return view('parent.messages', compact('account','guardian','students','threads'));
    }

    // ── Setup parent account (called by admin) ─────────────────────────
    public function setupAccount(Request $request)
    {
        $data = $request->validate([
            'guardian_id' => ['required', Rule::exists('guardians', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'email'       => ['required','email'],
            'password'    => ['required','min:6'],
        ]);

        ParentPortalAccount::updateOrCreate(
            ['tenant_id' => auth()->user()->tenant_id, 'guardian_id' => $data['guardian_id']],
            [
                'tenant_id'  => auth()->user()->tenant_id,
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'is_active' => true,
            ]
        );
        return back()->with('success', 'Parent portal access created.');
    }
}
