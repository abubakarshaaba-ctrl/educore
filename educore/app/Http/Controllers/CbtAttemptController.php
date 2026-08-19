<?php

namespace App\Http\Controllers;

use App\Models\CbtStudentSession;
use App\Services\Cbt\CbtIntegrityService;
use App\Services\Cbt\CbtSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CbtAttemptController extends Controller
{
    public function autosave(Request $request, CbtStudentSession $session, CbtSubmissionService $submission)
    {
        $this->authorizeStudentSession($session);
        abort_if($session->isFinal(), 409, 'This attempt is already final.');
        $data = $request->validate(['answers' => ['nullable', 'array'], 'essay_answers' => ['nullable', 'array'], 'flagged_questions' => ['nullable', 'array']]);
        $session->loadMissing('exam');
        $deadline = $session->started_at?->copy()->addMinutes((int) $session->exam->duration_minutes);
        if ($session->exam->scheduled_end && (! $deadline || $session->exam->scheduled_end->lt($deadline))) $deadline = $session->exam->scheduled_end;
        if ($deadline && now()->gte($deadline)) {
            $final = $submission->submit($session, $data['answers'] ?? [], $data['essay_answers'] ?? [], 'time_expired', true);
            return response()->json(['ok' => false, 'submitted' => true, 'status' => $final->status], 409);
        }
        DB::transaction(function () use ($session, $data) {
            $locked = CbtStudentSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->isFinal(), 409, 'This attempt is already final.');
            $locked->update([
                'answers' => array_replace((array) $locked->answers, (array) ($data['answers'] ?? [])),
                'essay_answers' => array_replace((array) $locked->essay_answers, (array) ($data['essay_answers'] ?? [])),
                'flagged_questions' => $data['flagged_questions'] ?? $locked->flagged_questions,
                'last_synced_at' => now(),
            ]);
        });
        return response()->json(['ok' => true, 'saved_at' => now()->toIso8601String()]);
    }

    public function integrity(Request $request, CbtStudentSession $session, CbtIntegrityService $integrity)
    {
        $this->authorizeStudentSession($session);
        $data = $request->validate([
            'event_uuid' => ['required', 'uuid'], 'event_type' => ['required', 'string', 'max:80'],
            'metadata' => ['nullable', 'array'], 'answers' => ['nullable', 'array'], 'essay_answers' => ['nullable', 'array'],
        ]);
        return response()->json($integrity->record(
            $session, $data['event_uuid'], $data['event_type'], $data['metadata'] ?? [],
            $data['answers'] ?? [], $data['essay_answers'] ?? [], $request->ip(), $request->userAgent()
        ));
    }

    private function authorizeStudentSession(CbtStudentSession $session): void
    {
        $user = auth()->user();
        $student = \App\Models\Student::where('user_id', $user?->id)->first();
        abort_unless($user?->isStudent() && $student && (int) $student->id === (int) $session->student_id && (int) $user->tenant_id === (int) $session->tenant_id, 403);
    }
}
