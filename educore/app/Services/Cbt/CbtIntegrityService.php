<?php

namespace App\Services\Cbt;

use App\Models\CbtIntegrityEvent;
use App\Models\CbtStudentSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CbtIntegrityService
{
    public function __construct(private readonly CbtSubmissionService $submission) {}

    public function record(CbtStudentSession $session, string $uuid, string $type, array $metadata = [], array $answers = [], array $essayAnswers = [], ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $normalized = in_array($type, ['visibility_hidden', 'window_blur', 'page_hidden', 'focus_lost', 'fullscreen_exit'], true)
            ? 'exam_page_hidden_or_focus_lost' : $type;

        $created = false;
        DB::transaction(function () use ($session, $uuid, $normalized, $metadata, $ipAddress, $userAgent, &$created) {
            $locked = CbtStudentSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $event = CbtIntegrityEvent::firstOrCreate(['event_uuid' => $uuid], [
                'tenant_id' => $locked->tenant_id, 'cbt_exam_id' => $locked->cbt_exam_id,
                'cbt_student_session_id' => $locked->id, 'student_id' => $locked->student_id,
                'event_type' => $normalized, 'severity' => $normalized === 'exam_page_hidden_or_focus_lost' ? 'critical' : 'warning',
                'ip_address' => $ipAddress, 'user_agent' => $userAgent,
                'metadata' => $metadata, 'occurred_at' => now(),
            ]);
            $created = $event->wasRecentlyCreated;
            if ($created && $normalized === 'exam_page_hidden_or_focus_lost' && ! $locked->isFinal()) {
                $locked->increment('focus_loss_count');
            }
        });

        $session->refresh()->load('exam');
        $shouldSubmit = $created && ! $session->isFinal() && $session->exam->malpractice_enabled
            && $session->exam->focus_loss_policy === 'submit'
            && $session->focus_loss_count > $session->exam->max_focus_losses;
        if ($shouldSubmit) {
            $this->logSystemEvent($session, 'auto_submission_triggered', 'critical', $uuid, $ipAddress, $userAgent);
            $session = $this->submission->submit($session, $answers, $essayAnswers, 'integrity_focus_loss', true);
            $this->logSystemEvent($session, 'auto_submission_completed', 'critical', $uuid, $ipAddress, $userAgent);
        }
        return ['recorded' => $created, 'submitted' => $shouldSubmit, 'status' => $session->status, 'focus_loss_count' => $session->focus_loss_count];
    }

    private function logSystemEvent(CbtStudentSession $session, string $type, string $severity, string $triggerUuid, ?string $ipAddress, ?string $userAgent): void
    {
        CbtIntegrityEvent::create([
            'tenant_id' => $session->tenant_id, 'cbt_exam_id' => $session->cbt_exam_id,
            'cbt_student_session_id' => $session->id, 'student_id' => $session->student_id,
            'event_uuid' => (string) Str::uuid(), 'event_type' => $type, 'severity' => $severity,
            'ip_address' => $ipAddress, 'user_agent' => $userAgent,
            'metadata' => ['trigger_event_uuid' => $triggerUuid], 'occurred_at' => now(),
        ]);
    }
}
