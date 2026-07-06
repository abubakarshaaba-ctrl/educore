<?php

namespace App\Services\Notifications;

use App\Models\DeviceToken;
use App\Models\ExamPeriod;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications via Firebase Cloud Messaging (HTTP v1 API).
 *
 * Deliberately dependency-free: this host has no shell/composer access for
 * the deployed vendor/ tree, so instead of firebase/php-jwt we sign the
 * OAuth2 service-account JWT manually with PHP's built-in openssl
 * extension and exchange it for an access token with Http (already used
 * elsewhere in the app).
 *
 * Setup: create a Firebase project, download the service-account JSON
 * (Project Settings -> Service Accounts -> Generate new private key), and
 * set FCM_CREDENTIALS_PATH / FCM_PROJECT_ID in .env.
 */
class PushNotificationService
{
    public function notifyExamSupervisionPublished(ExamPeriod $period): void
    {
        $byUser = $period->entries()->with(['examSession', 'supervisors'])->get()
            ->flatMap->supervisors
            ->groupBy('user_id');

        foreach ($byUser as $userId => $supervisorRows) {
            $user = User::find($userId);
            if (!$user) continue;

            $count = $supervisorRows->count();
            $this->sendToUser(
                $user,
                'Exam Supervision Schedule',
                "You have {$count} supervision " . ($count === 1 ? 'duty' : 'duties') . " for {$period->title}. Open EduCore to view.",
                ['type' => 'exam_supervision', 'exam_period_id' => (string) $period->id]
            );
        }
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::where('user_id', $user->id)->pluck('token');
        foreach ($tokens as $token) {
            $this->send($token, $title, $body, $data);
        }
    }

    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $projectId = config('services.fcm.project_id');
        $accessToken = $this->accessToken();

        if (!$projectId || !$accessToken) {
            Log::warning('FCM not configured — skipping push send.');
            return false;
        }

        $response = Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => array_map('strval', $data),
                    'android' => ['priority' => 'high'],
                ],
            ]);

        if (!$response->successful()) {
            Log::warning('FCM send failed: ' . $response->body());
        }

        return $response->successful();
    }

    /** Cached (55 min) OAuth2 access token for the FCM service account. */
    private function accessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3300, function () {
            $path = config('services.fcm.credentials');
            if (!$path || !file_exists($path)) {
                return null;
            }

            $account = json_decode(file_get_contents($path), true);
            if (!$account || empty($account['private_key']) || empty($account['client_email'])) {
                return null;
            }

            $now = time();
            $header = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->b64(json_encode([
                'iss'   => $account['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'exp'   => $now + 3600,
                'iat'   => $now,
            ]));

            $unsigned = "{$header}.{$claims}";
            openssl_sign($unsigned, $signature, $account['private_key'], 'SHA256');
            $jwt = $unsigned . '.' . $this->b64($signature);

            $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            return $res->successful() ? $res->json('access_token') : null;
        });
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
