<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FirebaseCloudMessaging
{
    private const TOKEN_CACHE_KEY = 'educore:fcm:oauth-access-token';

    /**
     * Send a high-priority data-only FCM message to a topic.
     *
     * Data-only delivery is intentional: it guarantees EduCoreMessagingService
     * gets a chance to build the notification with the correct EduCore icon and
     * update deep-link even while the app is in the background.
     */
    public function sendToTopic(string $topic, array $data): bool
    {
        try {
            $projectId = trim((string) config('services.fcm.project_id'));
            if ($projectId === '') {
                throw new RuntimeException('FCM_PROJECT_ID is not configured.');
            }

            $accessToken = $this->accessToken();
            $topic = preg_replace('#^/topics/#', '', trim($topic));
            if ($topic === '') {
                throw new RuntimeException('FCM topic is empty.');
            }

            $stringData = [];
            foreach ($data as $key => $value) {
                if ($value === null) {
                    continue;
                }
                $stringData[(string) $key] = is_bool($value)
                    ? ($value ? 'true' : 'false')
                    : (string) $value;
            }

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(20)
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        'message' => [
                            'topic' => $topic,
                            'data' => $stringData,
                            'android' => [
                                'priority' => 'HIGH',
                            ],
                        ],
                    ],
                );

            if (! $response->successful()) {
                Log::warning('FCM topic notification failed.', [
                    'topic' => $topic,
                    'status' => $response->status(),
                    'response' => $response->json() ?: $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('FCM topic notification could not be sent.', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function accessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(50), function (): string {
            $credentials = $this->credentials();
            $clientEmail = trim((string) ($credentials['client_email'] ?? ''));
            $privateKey = (string) ($credentials['private_key'] ?? '');

            if ($clientEmail === '' || $privateKey === '') {
                throw new RuntimeException('The Firebase service-account credentials are incomplete.');
            }

            $now = time();
            $header = $this->base64Url(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ], JSON_UNESCAPED_SLASHES));
            $claims = $this->base64Url(json_encode([
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_UNESCAPED_SLASHES));

            $unsigned = "{$header}.{$claims}";
            $signature = '';
            if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Unable to sign the Firebase OAuth assertion.');
            }

            $assertion = $unsigned.'.'.$this->base64Url($signature);
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Firebase OAuth token request failed with HTTP '.$response->status().'.');
            }

            $token = trim((string) $response->json('access_token'));
            if ($token === '') {
                throw new RuntimeException('Firebase OAuth response did not contain an access token.');
            }

            return $token;
        });
    }

    private function credentials(): array
    {
        $inline = trim((string) config('services.fcm.credentials_json'));
        if ($inline !== '') {
            $decoded = json_decode($inline, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            throw new RuntimeException('FCM_CREDENTIALS_JSON is not valid JSON.');
        }

        $path = trim((string) config('services.fcm.credentials'));
        if ($path === '' || ! is_file($path)) {
            throw new RuntimeException('Firebase service-account credentials file was not found.');
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Firebase service-account credentials file is not valid JSON.');
        }

        return $decoded;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
