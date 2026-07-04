<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Score;
use App\Models\Term;
use App\Services\AcademicCycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademicSessionController extends Controller
{
    public function __construct(private AcademicCycleService $academicCycle) {}

    private function tenantId(): int { return (int) auth()->user()->tenant_id; }

    private function authorize(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->canAccessModule('academic-cycle')), 403);
    }

    private function getSessions()
    {
        return AcademicSession::where('tenant_id', $this->tenantId())
            ->withCount('terms')
            ->orderByDesc('is_current')->orderByDesc('id')->get();
    }

    private function getTerms()
    {
        return Term::with('session')
            ->where('tenant_id', $this->tenantId())
            ->orderByDesc('is_current')->orderByDesc('start_date')->orderByDesc('id')->get();
    }

    public function index(): View
    {
        $this->authorize();
        $tenantId       = $this->tenantId();
        $currentSession = $this->academicCycle->currentSessionForTenant($tenantId);
        $currentTerm    = $this->academicCycle->currentTermForTenant($tenantId);
        $sessions       = $this->getSessions();
        $terms          = $this->getTerms();
        return view('academic session.index', compact('currentSession', 'currentTerm', 'sessions', 'terms'));
    }

    public function sessions(): RedirectResponse  { return redirect()->route('academic-cycle.index'); }
    public function terms(): RedirectResponse     { return redirect()->route('academic-cycle.index'); }
    public function readiness(): RedirectResponse { return redirect()->route('academic-cycle.index'); }
    public function repair(): RedirectResponse    { return redirect()->route('academic-cycle.index'); }

    /* ── SESSIONS ─────────────────────────────────────────────── */

    public function storeSession(Request $request): RedirectResponse
    {
        $this->authorize();
        try {
            $this->academicCycle->createSession($this->tenantId(), $request->only(['name','activate']), $request->user(), $request);
            return redirect()->route('academic-cycle.index')->with('success', 'Academic session created.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    public function updateSession(Request $request, AcademicSession $session): RedirectResponse
    {
        $this->authorize();
        $tenantId = $this->tenantId();
        abort_if($session->tenant_id !== $tenantId, 403);
        $data = $request->validate(['name' => ['required','string','max:100',Rule::unique('academic_sessions')->where('tenant_id',$tenantId)->ignore($session->id)]]);
        $session->update(['name' => trim($data['name'])]);
        return redirect()->route('academic-cycle.index')->with('success', "Session name updated.");
    }

    public function activateSession(Request $request, AcademicSession $session): RedirectResponse
    {
        $this->authorize();
        abort_if($session->tenant_id !== $this->tenantId(), 403);
        try {
            $this->academicCycle->activateSession($this->tenantId(), $session, $request->user(), $request);
            return back()->with('success', "\"{$session->name}\" is now the current session.");
        } catch (\Illuminate\Validation\ValidationException $e) { return back()->withErrors($e->errors()); }
    }

    public function closeSession(Request $request, AcademicSession $session): RedirectResponse
    {
        $this->authorize();
        abort_if($session->tenant_id !== $this->tenantId(), 403);
        try {
            $this->academicCycle->closeSession($this->tenantId(), $session, $request->user(), $request);
            return back()->with('success', "Session \"{$session->name}\" closed.");
        } catch (\Illuminate\Validation\ValidationException $e) { return back()->withErrors($e->errors()); }
    }

    public function destroySession(AcademicSession $session): RedirectResponse
    {
        $this->authorize();
        $tenantId = $this->tenantId();
        abort_if($session->tenant_id !== $tenantId, 403);
        if ($session->is_current) return back()->withErrors(['error' => 'Cannot delete the active session. Close it first.']);
        $termCount = Term::where('session_id',$session->id)->where('tenant_id',$tenantId)->count();
        if ($termCount > 0) return back()->withErrors(['error' => "Cannot delete \"{$session->name}\" — it has {$termCount} term(s). Delete all terms first."]);
        $name = $session->name;
        $session->delete();
        return redirect()->route('academic-cycle.index')->with('success', "Session \"{$name}\" deleted.");
    }

    /* ── TERMS ────────────────────────────────────────────────── */

    public function storeTerm(Request $request): RedirectResponse
    {
        $this->authorize();
        try {
            $this->academicCycle->createTerm($this->tenantId(), $request->only(['session_id','name','start_date','end_date','next_term_begins','activate']), $request->user(), $request);
            return redirect()->route('academic-cycle.index')->with('success', 'Term created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) { return back()->withErrors($e->errors())->withInput(); }
    }

    public function updateTerm(Request $request, Term $term): RedirectResponse
    {
        $this->authorize();
        $tenantId = $this->tenantId();
        abort_if($term->tenant_id !== $tenantId, 403);
        $data = $request->validate([
            'name'              => ['required','string','max:100',Rule::unique('terms')->where(fn($q)=>$q->where('tenant_id',$tenantId)->where('session_id',$term->session_id))->ignore($term->id)],
            'start_date'        => ['required','date'],
            'end_date'          => ['required','date','after_or_equal:start_date'],
            'next_term_begins'  => ['nullable','date','after:end_date'],
        ]);
        $term->update($data);
        return redirect()->route('academic-cycle.index')->with('success', "Term updated.");
    }

    public function activateTerm(Request $request, Term $term): RedirectResponse
    {
        $this->authorize();
        abort_if($term->tenant_id !== $this->tenantId(), 403);
        try {
            $this->academicCycle->activateTerm($this->tenantId(), $term, $request->user(), $request);
            return back()->with('success', "\"{$term->name}\" is now the current term.");
        } catch (\Illuminate\Validation\ValidationException $e) { return back()->withErrors($e->errors()); }
    }

    public function closeTerm(Request $request, Term $term): RedirectResponse
    {
        $this->authorize();
        abort_if($term->tenant_id !== $this->tenantId(), 403);
        try {
            $this->academicCycle->closeTerm($this->tenantId(), $term, $request->user(), $request);
            return back()->with('success', "\"{$term->name}\" is now closed.");
        } catch (\Illuminate\Validation\ValidationException $e) { return back()->withErrors($e->errors()); }
    }

    public function destroyTerm(Term $term): RedirectResponse
    {
        $this->authorize();
        $tenantId = $this->tenantId();
        abort_if($term->tenant_id !== $tenantId, 403);
        if ($term->is_current) return back()->withErrors(['error' => 'Cannot delete the active term. Close it first.']);
        $scoreCount = Score::where('term_id',$term->id)->count();
        if ($scoreCount > 0) return back()->withErrors(['error' => "Cannot delete \"{$term->name}\" — {$scoreCount} score record(s) are linked."]);
        $name = $term->name;
        $term->delete();
        return redirect()->route('academic-cycle.index')->with('success', "Term \"{$name}\" deleted.");
    }

}
