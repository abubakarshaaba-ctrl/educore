<?php

namespace App\Services\Notifications;

use App\Models\Announcement;
use App\Models\ExamPeriod;
use App\Models\Guardian;
use App\Models\MessageThread;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\GuardianMailNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class ActivityEmailService
{
    public function notifyAnnouncementPublished(Announcement $announcement): int
    {
        $tenant = Tenant::find($announcement->tenant_id);
        if (! $tenant) {
            Log::warning('Announcement email skipped because tenant was not found.', [
                'announcement_id' => $announcement->id,
                'tenant_id' => $announcement->tenant_id,
            ]);

            return 0;
        }

        $recipients = $this->announcementRecipients($announcement);
        $subject = $announcement->priority === 'urgent'
            ? 'Urgent: '.$announcement->title
            : $announcement->title;
        $body = trim(strip_tags((string) $announcement->body));
        $lines = $body !== ''
            ? [$body]
            : ['A new notice has been published by '.$tenant->name.'.'];

        return $this->sendToRecipients(
            $recipients,
            $subject,
            $lines,
            $tenant,
            ['announcement_id' => $announcement->id]
        );
    }

    public function notifyAttendanceStatus(Student $student, string $status, string|Carbon $date): int
    {
        if (! in_array($status, ['absent', 'late'], true)) {
            return 0;
        }

        $student->loadMissing(['guardians', 'tenant']);
        $tenant = $student->tenant;
        if (! $tenant) {
            return 0;
        }

        $guardian = $student->guardians
            ->first(fn (Guardian $guardian) => (bool) $guardian->pivot?->is_primary_contact && filled($guardian->email))
            ?? $student->guardians->first(fn (Guardian $guardian) => filled($guardian->email));

        if (! $guardian?->email) {
            return 0;
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $formattedDate = $date->format('d M Y');
        $studentName = $student->full_name;

        if ($status === 'absent') {
            $subject = "Attendance notice: {$studentName} absent on {$formattedDate}";
            $lines = [
                "{$studentName} was marked absent from school on {$formattedDate}.",
                'Please contact the school if this is unexpected or if the attendance record needs correction.',
            ];
        } else {
            $subject = "Attendance notice: {$studentName} arrived late on {$formattedDate}";
            $lines = [
                "{$studentName} was marked late to school on {$formattedDate}.",
                'Please contact the school if this is unexpected or if the attendance record needs correction.',
            ];
        }

        return $this->sendToRecipients(
            [[
                'email' => $guardian->email,
                'name' => $guardian->full_name ?: 'Parent/Guardian',
            ]],
            $subject,
            $lines,
            $tenant,
            ['student_id' => $student->id, 'attendance_status' => $status]
        );
    }

    public function notifyMessageThread(MessageThread $thread, User $sender, string $body): int
    {
        $tenant = Tenant::find($thread->tenant_id);
        if (! $tenant) {
            return 0;
        }

        $recipientIds = collect([$thread->initiated_by, $thread->recipient_user_id])
            ->merge($thread->replies()->pluck('sender_id'));
        $directGuardianRecipients = collect();

        if ($thread->student_id) {
            $thread->loadMissing(['student.guardians']);
            if ($thread->student?->user_id) {
                $recipientIds->push($thread->student->user_id);
            }

            foreach ($thread->student?->guardians ?? collect() as $guardian) {
                if ($guardian->user_id) {
                    $recipientIds->push($guardian->user_id);
                }
                if (filled($guardian->email)) {
                    $directGuardianRecipients->push([
                        'email' => $guardian->email,
                        'name' => $guardian->full_name ?: 'Parent/Guardian',
                    ]);
                }
            }
        } elseif ($thread->audience === 'all_staff') {
            $recipientIds = $recipientIds->merge(
                User::query()
                    ->where('tenant_id', $thread->tenant_id)
                    ->where('is_active', true)
                    ->whereIn('role', User::staffRoleNames())
                    ->pluck('id')
            );
        } elseif ($thread->audience === 'all_parents') {
            $recipientIds = $recipientIds->merge(
                User::query()
                    ->where('tenant_id', $thread->tenant_id)
                    ->where('is_active', true)
                    ->where('role', 'parent')
                    ->pluck('id')
            );

            Guardian::query()
                ->where('tenant_id', $thread->tenant_id)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->whereHas('students', fn ($query) => $query->where('students.status', Student::STATUS_ACTIVE))
                ->get(['id', 'first_name', 'last_name', 'email'])
                ->each(fn (Guardian $guardian) => $directGuardianRecipients->push([
                    'email' => $guardian->email,
                    'name' => $guardian->full_name ?: 'Parent/Guardian',
                ]));
        }

        $recipientIds = $recipientIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $sender->id)
            ->unique()
            ->values();

        $recipients = User::query()
            ->where('tenant_id', $thread->tenant_id)
            ->whereIn('id', $recipientIds)
            ->where('is_active', true)
            ->where('is_super_admin', false)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => ['email' => $user->email, 'name' => $user->name ?: 'EduCore User'])
            ->merge($directGuardianRecipients)
            ->reject(fn (array $recipient) => Str::lower(trim((string) $recipient['email'])) === Str::lower(trim((string) $sender->email)))
            ->filter(fn (array $recipient) => filter_var($recipient['email'] ?? null, FILTER_VALIDATE_EMAIL))
            ->unique(fn (array $recipient) => Str::lower(trim($recipient['email'])))
            ->values()
            ->all();

        $cleanBody = trim(strip_tags($body));

        return $this->sendToRecipients(
            $recipients,
            'New message: '.Str::limit($thread->subject, 120),
            [
                $sender->name.' sent a new message in EduCore.',
                Str::limit($cleanBody, 1200),
            ],
            $tenant,
            ['message_thread_id' => $thread->id, 'sender_id' => $sender->id]
        );
    }

    public function notifyExamSupervisionPublished(ExamPeriod $period): int
    {
        $tenant = Tenant::find($period->tenant_id);
        if (! $tenant) {
            return 0;
        }

        $supervisorRows = $period->entries()
            ->with(['examSession', 'supervisors.user'])
            ->get()
            ->flatMap->supervisors
            ->filter(fn ($row) => $row->user && $row->user->is_active && filled($row->user->email))
            ->groupBy('user_id');

        $sent = 0;
        foreach ($supervisorRows as $rows) {
            $user = $rows->first()?->user;
            if (! $user) {
                continue;
            }

            $count = $rows->count();
            $sent += $this->sendToRecipients(
                [['email' => $user->email, 'name' => $user->name ?: 'Staff Member']],
                'Exam Supervision Schedule: '.$period->title,
                [
                    "You have {$count} exam supervision ".($count === 1 ? 'duty' : 'duties')." for {$period->title}.",
                    'The supervision schedule has been published. Sign in to EduCore to view your assigned dates, sessions and venues.',
                ],
                $tenant,
                ['exam_period_id' => $period->id, 'user_id' => $user->id]
            );
        }

        return $sent;
    }

    private function announcementRecipients(Announcement $announcement): array
    {
        $tenantId = (int) $announcement->tenant_id;
        $recipients = collect();

        if ($announcement->audience !== 'parents') {
            $users = User::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('is_super_admin', false)
                ->whereNotNull('email')
                ->where('email', '!=', '');

            match ($announcement->audience) {
                'staff' => $users->whereIn('role', User::ROLES_STAFF),
                'students' => $users->where('role', 'student'),
                'admin' => $users->whereIn('role', ['admin', 'principal', 'vice_principal', 'head', 'head_teacher']),
                default => null,
            };

            $users->select(['name', 'email'])->orderBy('id')->chunk(250, function ($chunk) use (&$recipients): void {
                foreach ($chunk as $user) {
                    $recipients->push([
                        'email' => $user->email,
                        'name' => $user->name ?: 'EduCore User',
                    ]);
                }
            });
        }

        if (in_array($announcement->audience, ['parents', 'all'], true)) {
            Guardian::query()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->whereHas('students', fn ($query) => $query->where('students.status', Student::STATUS_ACTIVE))
                ->select(['id', 'first_name', 'last_name', 'email'])
                ->orderBy('id')
                ->chunk(250, function ($guardians) use (&$recipients): void {
                    foreach ($guardians as $guardian) {
                        $recipients->push([
                            'email' => $guardian->email,
                            'name' => $guardian->full_name ?: 'Parent/Guardian',
                        ]);
                    }
                });
        }

        return $recipients
            ->filter(fn (array $recipient) => filter_var($recipient['email'] ?? null, FILTER_VALIDATE_EMAIL))
            ->unique(fn (array $recipient) => Str::lower(trim($recipient['email'])))
            ->values()
            ->all();
    }

    private function sendToRecipients(
        array $recipients,
        string $subject,
        array $lines,
        Tenant $tenant,
        array $context = []
    ): int {
        $sent = 0;

        foreach ($recipients as $recipient) {
            try {
                Notification::route('mail', $recipient['email'])->notify(
                    new GuardianMailNotification(
                        subject: $subject,
                        greetingName: $recipient['name'] ?: 'EduCore User',
                        lines: $lines,
                        schoolName: $tenant->name,
                        replyToEmail: filter_var($tenant->email, FILTER_VALIDATE_EMAIL) ? $tenant->email : null,
                    )
                );
                $sent++;
            } catch (Throwable $exception) {
                Log::warning('Activity email delivery failed.', array_merge($context, [
                    'tenant_id' => $tenant->id,
                    'error' => $exception->getMessage(),
                ]));
            }
        }

        return $sent;
    }
}
