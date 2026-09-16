<?php

namespace App\Services\Notifications;

use App\Models\Announcement;
use App\Models\ExamPeriod;
use App\Models\MessageThread;
use App\Models\NotificationLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Compatibility facade for activity communication hooks.
 *
 * Transactional email is intentionally reserved for high-value events handled
 * by their dedicated mail flows (account/security, results, finance, admissions,
 * payroll, recruitment and critical tenant/account lifecycle events).
 * Routine operational communication must remain push + in-app.
 */
class ActivityEmailService
{
    /** General school announcements are push + in-app only. */
    public function notifyAnnouncementPublished(Announcement $announcement): int
    {
        return 0;
    }

    /**
     * Attendance is explicitly excluded from email. Deliver it to the relevant
     * portal accounts through FCM and persist an in-app notification log.
     */
    public function notifyAttendanceStatus(Student $student, string $status, string|Carbon $date): int
    {
        if (! in_array($status, ['absent', 'late'], true)) {
            return 0;
        }

        $student->loadMissing(['guardians']);
        $tenantId = (int) $student->tenant_id;
        if ($tenantId <= 0) {
            return 0;
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $formattedDate = $date->format('d M Y');
        $studentName = $student->full_name;

        if ($status === 'absent') {
            $title = 'Attendance notice';
            $message = "{$studentName} was marked absent on {$formattedDate}.";
        } else {
            $title = 'Attendance notice';
            $message = "{$studentName} was marked late on {$formattedDate}.";
        }

        $recipientIds = collect([$student->user_id])
            ->merge($student->guardians->pluck('user_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            return 0;
        }

        $push = app(PushNotificationService::class);
        $delivered = 0;

        User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $recipientIds)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->each(function (User $user) use ($push, $title, $message, $student, $status, $date, &$delivered): void {
                $push->sendToUser($user, $title, $message, [
                    'type' => 'attendance',
                    'student_id' => (string) $student->id,
                    'attendance_status' => $status,
                    'attendance_date' => $date->toDateString(),
                    'destination_type' => 'attendance',
                    'destination_id' => (string) $student->id,
                ]);

                NotificationLog::create([
                    'tenant_id' => $student->tenant_id,
                    'student_id' => $student->id,
                    'guardian_id' => $student->guardians
                        ->first(fn ($guardian) => (int) ($guardian->user_id ?? 0) === (int) $user->id)?->id,
                    'channel' => 'in_app',
                    'recipient' => 'user:'.$user->id,
                    'subject' => $title,
                    'message' => $message,
                    'status' => 'delivered',
                    'gateway_response' => [
                        'type' => 'attendance',
                        'user_id' => $user->id,
                        'attendance_status' => $status,
                        'attendance_date' => $date->toDateString(),
                    ],
                    'unit_cost' => 0,
                    'sent_at' => now(),
                ]);

                $delivered++;
            });

        return $delivered;
    }

    /** EduCore conversations are already in-app and receive push notifications. */
    public function notifyMessageThread(MessageThread $thread, User $sender, string $body): int
    {
        return 0;
    }

    /** Exam supervision schedules are app-visible and receive push notifications. */
    public function notifyExamSupervisionPublished(ExamPeriod $period): int
    {
        return 0;
    }
}
