<?php

namespace App\Http\Middleware;

use App\Models\CbtStudentSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictCbtLanSession
{
    private const ALLOWED_ROUTES = [
        'cbt.lan.student.access',
        'cbt.lan.student.authenticate',
        'student.portal.exams',
        'cbt.exams.start',
        'cbt.exams.begin',
        'cbt.session.submit',
        'cbt.session.autosave',
        'cbt.session.integrity',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!(bool) $request->session()->get('cbt_lan_only', false)) {
            return $next($request);
        }

        if (!auth()->user()?->isStudent()) {
            $request->session()->forget(['cbt_lan_only', 'cbt_lan_exam_ids', 'cbt_lan_student_id']);
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (!in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return $this->deny($request);
        }

        $allowedExamIds = collect($request->session()->get('cbt_lan_exam_ids', []))
            ->map(fn ($id) => (int) $id);
        $exam = $request->route('exam');
        if ($exam && !$allowedExamIds->contains((int) ($exam->id ?? $exam))) {
            return $this->deny($request);
        }

        $session = $request->route('session');
        if ($session) {
            $attempt = $session instanceof CbtStudentSession
                ? $session
                : CbtStudentSession::find($session);
            if (!$attempt
                || !$allowedExamIds->contains((int) $attempt->cbt_exam_id)
                || (int) $attempt->student_id !== (int) $request->session()->get('cbt_lan_student_id')) {
                return $this->deny($request);
            }
        }

        return $next($request);
    }

    private function deny(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'This LAN session is restricted to CBT examinations.'], 403);
        }

        return redirect()->route('student.portal.exams');
    }
}
