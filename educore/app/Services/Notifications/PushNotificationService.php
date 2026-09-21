<?php

namespace App\Services\Notifications;

use App\Models\Announcement;
use App\Models\DeviceToken;
use App\Models\ExamPeriod;
use App\Models\MessageThread;
use App\Models\User;
use App\Support\EduCoreRichText;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends push notifications through Firebase Cloud Messaging HTTP v1.
 *
 * EduCore Android pushes use a data-only, high-priority FCM contract.
 *
 * The native FirebaseMessagingService is the single notification renderer for
 * foreground, background and terminated-process delivery. Keeping rendering
 * in one place preserves deep links, channel selection, icon/branding and
 * version gating consistently. Do not add a top-level FCM "notification"
 * payload here; doing so lets Android bypass the native service in background
 * delivery and recreates the notification regression fixed in September 2026.
 */
class PushNotificationService
{
    public function notifyAnnouncementPublished(Announcement $announcement): array
    {
        $query = User::query()
            ->where('tenant_id', $announcement->tenant_id)
            ->where('is_active', true)
            ->where('is_super_admin', false);

        if ($announcement->platform_broadcast_id !== null) {
            // Platform broadcasts are a distinct communication channel:
            // selected schools stay governed by the broadcast target, while
            // recipients inside each school are tenant administrators only.
            $query->whereIn('role', User::roleAliasesFor('admin'));
        } else {
            match ($announcement->audience) {
                'staff' => $query->whereIn('role', User::staffRoleNames()),
                'students' => $query->where('role', 'student'),
                'parents' => $query->where('role', 'parent'),
                'admin' => $query->whereIn('role', ['admin', 'principal', 'vice_principal']),
                default => $query,
            };
        }

        $stats = ['users' => 0, 'tokens' => 0, 'sent' => 0, 'failed' => 0];

        $query->orderBy('id')->chunkById(100, function ($users) use ($announcement, &$stats) {
            foreach ($users as $user) {
                $stats['users']++;
                $result = $this->sendToUser(
                    $user,
                    $announcement->priority === 'urgent'
                        ? 'Urgent school announcement'
                        : 'New school announcement',
                    Str::limit(EduCoreRichText::plainText($announcement->title.': '.$announcement->body), 180),
                    [
                        'type' => 'announcement',
                        'announcement_id' => (string) $announcement->id,
                        'destination_type' => 'announcement',
                        'destination_id' => (string) $announcement->id,
                    ],
                );
                $stats['tokens'] += $result['tokens'];
                $stats['sent'] += $result['sent'];
                $stats['failed'] += $result['failed'];
            }
        });

        return $stats;
    }

    public function notifyMessageThread(MessageThread $thread, User $sender, string $body): void
    {
        if ($thread->student_id) {
            $thread->loadMissing(['student.guardians']);
            $recipientIds = collect([$thread->initiated_by])
                ->merge($thread->replies()->pluck('sender_id'))
                ->when($thread->student?->user_id, fn ($ids) => $ids->push($thread->student->user_id))
                ->merge($thread->student?->guardians?->pluck('user_id')->filter() ?? collect());
        } else {
            $recipientIds = collect([$thread->initiated_by, $thread->recipient_user_id])
                ->merge($thread->replies()->pluck('sender_id'));

            if ($thread->audience === 'all_staff') {
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
            }
        }

        $recipientIds = $recipientIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $sender->id)
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        User::query()
            ->where('tenant_id', $thread->tenant_id)
            ->whereIn('id', $recipientIds)
            ->where('is_active', true)
            ->get()
            ->each(fn (User $user) => $this->sendToUser(
                $user,
                'New message: '.Str::limit($thread->subject, 70),
                Str::limit($sender->name.': '.EduCoreRichText::plainText($body), 180),
                [
                    'type' => 'message',
                    'thread_id' => (string) $thread->id,
                    'destination_type' => 'message_thread',
                    'destination_id' => (string) $thread->id,
                ],
            ));
    }

    public function notifyExamSupervisionPublished(ExamPeriod $period): void
    {
        $byUser = $period->entries()->with(['examSession', 'supervisors'])->get()
            ->flatMap->supervisors
            ->groupBy('user_id');

        foreach ($byUser as $userId => $supervisorRows) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $count = $supervisorRows->count();
            $this->sendToUser(
                $user,
                'Exam Supervision Schedule',
                "You have {$count} supervision ".($count === 1 ? 'duty' : 'duties')." for {$period->title}. Open EduCore to view.",
                ['type' => 'exam_supervision', 'exam_period_id' => (string) $period->id],
            );
        }
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        $tokens = DeviceToken::where('user_id', $user->id)
            ->pluck('token')
            ->filter(fn ($token) => is_string($token) && trim($token) !== '')
            ->values();

        $stats = ['tokens' => $tokens->count(), 'sent' => 0, 'failed' => 0];
        foreach ($tokens as $token) {
            if ($this->send($token, $title, $body, $data)) {
                $stats['sent']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        return $this->sendMessage(
            ['token' => $deviceToken],
            $title,
            $body,
            array_merge($data, ['title' => $title, 'body' => $body]),
            $deviceToken,
        );
    }

    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        $topic = trim($topic);
        if ($topic === '') {
            return false;
        }

        return $this->sendMessage(
            ['topic' => $topic],
            $title,
            $body,
            array_merge($data, ['title' => $title, 'body' => $body]),
            null,
        );
    }

    private function sendMessage(
        array $target,
        string $title,
        string $body,
        array $data = [],
        ?string $deviceToken = null,
    ): bool {
        try {
            $projectId = config('services.fcm.project_id');
            $accessToken = $this->accessToken();

            if (! $projectId || ! $accessToken) {
                Log::warning('FCM not configured - skipping push send.');

                return false;
            }

            $message = array_merge($target, [
                'data' => array_map('strval', $data),
                'android' => [
                    'priority' => 'high',
                ],
            ]);

            $response = Http::timeout(15)
                ->retry(2, 250, throw: false)
                ->withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => $message,
                ]);

            if (! $response->successful()) {
                $failure = $response->json('error.details.0.errorCode')
                    ?? $response->json('error.status');

                if ($deviceToken !== null && in_array($failure, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'], true)) {
                    DeviceToken::where('token', $deviceToken)->delete();
                }

                Log::warning('FCM send failed.', [
                    'status' => $response->status(),
                    'failure' => $failure,
                    'target' => isset($target['topic']) ? 'topic:'.$target['topic'] : 'device',
                ]);
            }

            return $response->successful();
        } catch (\Throwable $exception) {
            Log::warning('FCM send could not be completed.', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /** Cached (55 min) OAuth2 access token for the FCM service account. */
    private function accessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3300, function () {
            $account = $this->serviceAccount();
            if (! $account || empty($account['private_key']) || empty($account['client_email'])) {
                return null;
            }

            $now = time();
            $header = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->b64(json_encode([
                'iss' => $account['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ]));

            $unsigned = "{$header}.{$claims}";
            if (! openssl_sign($unsigned, $signature, $account['private_key'], 'SHA256')) {
                return null;
            }
            $jwt = $unsigned.'.'.$this->b64($signature);
            $response = Http::timeout(15)
                ->asForm()
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            return $response->successful() ? $response->json('access_token') : null;
        });
    }

    private function serviceAccount(): ?array
    {
        $inline = config('services.fcm.credentials_json');
        if (is_string($inline) && trim($inline) !== '') {
            $decoded = json_decode($inline, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $path = config('services.fcm.credentials');
        if (! $path || ! file_exists($path)) {
            return null;
        }

        $decoded = json_decode(file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
