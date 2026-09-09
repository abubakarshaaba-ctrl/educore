<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Score;
use App\Models\Term;
use App\Models\User;
use App\Services\AcademicCycleDecision;
use App\Services\AcademicCycleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileAcademicCycleController extends Controller
{
    public function __construct(private AcademicCycleService $academicCycle) {}

    public function index(Request $request)
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $sessions = AcademicSession::where('tenant_id', $tenantId)
            ->withCount('terms')
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get()
            ->map(fn (AcademicSession $session): array => $this->sessionPayload($session));
        $terms = Term::where('tenant_id', $tenantId)
            ->with('session:id,name')
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Term $term): array => $this->termPayload($term));
        $currentSession = $this->academicCycle->currentSessionForTenant($tenantId);
        $currentTerm = $this->academicCycle->currentTermForTenant($tenantId);

        return response()->json([
            'contract_version' => 1,
            'capabilities' => ['manage' => $user->canManage('academic-cycle')],
            'current' => [
                'session_id' => $currentSession?->id,
                'session' => $currentSession?->name,
                'term_id' => $currentTerm?->id,
                'term' => $currentTerm?->name,
            ],
            'metrics' => [
                'sessions' => $sessions->count(),
                'terms' => $terms->count(),
                'current_session_ready' => $currentSession !== null,
                'current_term_ready' => $currentTerm !== null,
            ],
            'sessions' => $sessions->values(),
            'terms' => $terms->values(),
        ]);
    }

    public function storeSession(Request $request)
    {
        $user = $this->guard($request, manage: true);
        $session = $this->academicCycle->createSession(
            (int) $user->tenant_id,
            $request->only(['name', 'activate']),
            $user,
            $request,
        );
        $session->loadCount('terms');

        return response()->json([
            'message' => 'Academic session created.',
            'session' => $this->sessionPayload($session),
        ], 201);
    }

    public function updateSession(Request $request, int $session)
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;
        $record = AcademicSession::where('tenant_id', $tenantId)->findOrFail($session);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('academic_sessions', 'name')->where('tenant_id', $tenantId)->ignore($record->id)],
        ]);
        $record->update(['name' => trim($data['name'])]);
        $record->refresh()->loadCount('terms');

        return response()->json(['message' => 'Academic session updated.', 'session' => $this->sessionPayload($record)]);
    }

    public function activateSession(Request $request, int $session)
    {
        $user = $this->guard($request, manage: true);
        $record = $this->academicCycle->activateSession((int) $user->tenant_id, $session, $user, $request);
        $record->loadCount('terms');

        return response()->json(['message' => 'Academic session activated.', 'session' => $this->sessionPayload($record)]);
    }

    public function sessionReadiness(Request $request, int $session)
    {
        $user = $this->guard($request);
        AcademicSession::where('tenant_id', $user->tenant_id)->findOrFail($session);

        return response()->json($this->decisionPayload($this->academicCycle->sessionClosureReadiness((int) $user->tenant_id, $session)));
    }

    public function closeSession(Request $request, int $session)
    {
        $user = $this->guard($request, manage: true);
        $record = $this->academicCycle->closeSession((int) $user->tenant_id, $session, $user, $request);
        $record->loadCount('terms');

        return response()->json(['message' => 'Academic session closed.', 'session' => $this->sessionPayload($record)]);
    }

    public function destroySession(Request $request, int $session)
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;
        $record = AcademicSession::where('tenant_id', $tenantId)->findOrFail($session);
        if ($record->is_current) {
            throw ValidationException::withMessages(['session' => 'Cannot delete the active session. Close it first.']);
        }
        $termCount = Term::where('tenant_id', $tenantId)->where('session_id', $record->id)->count();
        if ($termCount > 0) {
            throw ValidationException::withMessages(['session' => "Cannot delete this session because it has {$termCount} term(s)."]);
        }
        $record->delete();

        return response()->json(['message' => 'Academic session deleted.']);
    }

    public function storeTerm(Request $request)
    {
        $user = $this->guard($request, manage: true);
        $term = $this->academicCycle->createTerm(
            (int) $user->tenant_id,
            $request->only(['session_id', 'name', 'start_date', 'end_date', 'next_term_begins', 'activate']),
            $user,
            $request,
        );
        $term->load('session:id,name');

        return response()->json(['message' => 'Academic term created.', 'term' => $this->termPayload($term)], 201);
    }

    public function updateTerm(Request $request, int $term)
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;
        $record = Term::where('tenant_id', $tenantId)->findOrFail($term);
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('terms', 'name')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)->where('session_id', $record->session_id))->ignore($record->id),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'next_term_begins' => ['nullable', 'date', 'after:end_date'],
        ]);
        $record->update($data);
        $record->refresh()->load('session:id,name');

        return response()->json(['message' => 'Academic term updated.', 'term' => $this->termPayload($record)]);
    }

    public function activateTerm(Request $request, int $term)
    {
        $user = $this->guard($request, manage: true);
        $record = $this->academicCycle->activateTerm((int) $user->tenant_id, $term, $user, $request);
        $record->load('session:id,name');

        return response()->json(['message' => 'Academic term activated.', 'term' => $this->termPayload($record)]);
    }

    public function termReadiness(Request $request, int $term)
    {
        $user = $this->guard($request);
        Term::where('tenant_id', $user->tenant_id)->findOrFail($term);

        return response()->json($this->decisionPayload($this->academicCycle->termClosureReadiness((int) $user->tenant_id, $term)));
    }

    public function closeTerm(Request $request, int $term)
    {
        $user = $this->guard($request, manage: true);
        $record = $this->academicCycle->closeTerm((int) $user->tenant_id, $term, $user, $request);
        $record->load('session:id,name');

        return response()->json(['message' => 'Academic term closed.', 'term' => $this->termPayload($record)]);
    }

    public function destroyTerm(Request $request, int $term)
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;
        $record = Term::where('tenant_id', $tenantId)->findOrFail($term);
        if ($record->is_current) {
            throw ValidationException::withMessages(['term' => 'Cannot delete the active term. Close it first.']);
        }
        $scoreCount = Score::where('tenant_id', $tenantId)->where('term_id', $record->id)->count();
        if ($scoreCount > 0) {
            throw ValidationException::withMessages(['term' => "Cannot delete this term because {$scoreCount} score record(s) are linked."]);
        }
        $record->delete();

        return response()->json(['message' => 'Academic term deleted.']);
    }

    private function decisionPayload(AcademicCycleDecision $decision): array
    {
        return [
            'allowed' => $decision->allowed,
            'blocking' => $decision->blocking,
            'warnings' => $decision->warnings,
            'information' => $decision->information,
        ];
    }

    private function sessionPayload(AcademicSession $session): array
    {
        return [
            'id' => $session->id,
            'name' => $session->name,
            'current' => (bool) $session->is_current,
            'term_count' => (int) ($session->terms_count ?? $session->terms()->count()),
        ];
    }

    private function termPayload(Term $term): array
    {
        return [
            'id' => $term->id,
            'session_id' => $term->session_id,
            'session' => $term->session?->name,
            'name' => $term->name,
            'start_date' => $term->start_date?->toDateString(),
            'end_date' => $term->end_date?->toDateString(),
            'next_term_begins' => $term->next_term_begins?->toDateString(),
            'current' => (bool) $term->is_current,
        ];
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'Academic cycle access required.');
        abort_unless($user->tenant_id, 403, 'Academic cycle access required.');
        abort_unless(
            $manage ? $user->canManage('academic-cycle') : $user->canAccessModule('academic-cycle'),
            403,
            $manage ? 'Academic cycle management permission required.' : 'Academic cycle access required.'
        );

        return $user;
    }
}
