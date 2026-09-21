<?php

namespace Tests\Unit;

use App\Services\Notifications\PushNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PushNotificationPayloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('fcm_access_token');

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($key);
        openssl_pkey_export($key, $privateKey);

        config([
            'services.fcm.project_id' => 'educore-test',
            'services.fcm.credentials_json' => json_encode([
                'client_email' => 'firebase-adminsdk@example.iam.gserviceaccount.com',
                'private_key' => $privateKey,
            ]),
            'services.fcm.credentials' => null,
        ]);
    }

    public function test_device_push_is_data_only_high_priority_and_native_rendered(): void
    {
        $sentMessage = null;
        Http::fake(function ($request) use (&$sentMessage) {
            if (str_contains($request->url(), 'oauth2.googleapis.com/token')) {
                return Http::response(['access_token' => 'test-access-token'], 200);
            }

            $sentMessage = $request->data()['message'] ?? null;

            return Http::response(['name' => 'projects/educore-test/messages/1'], 200);
        });

        $ok = app(PushNotificationService::class)->send(
            'device-token',
            'Test title',
            'Test body',
            [
                'type' => 'announcement',
                'destination_type' => 'announcement',
                'destination_id' => '5',
            ],
        );

        $this->assertTrue($ok);
        $this->assertSame('device-token', $sentMessage['token']);
        $this->assertArrayNotHasKey('notification', $sentMessage);
        $this->assertSame('high', $sentMessage['android']['priority']);
        $this->assertArrayNotHasKey('notification', $sentMessage['android']);
        $this->assertSame('announcement', $sentMessage['data']['type']);
        $this->assertSame('announcement', $sentMessage['data']['destination_type']);
        $this->assertSame('5', $sentMessage['data']['destination_id']);
        $this->assertSame('Test title', $sentMessage['data']['title']);
        $this->assertSame('Test body', $sentMessage['data']['body']);
    }

    public function test_app_update_topic_uses_the_same_data_only_contract(): void
    {
        $sentMessage = null;
        Http::fake(function ($request) use (&$sentMessage) {
            if (str_contains($request->url(), 'oauth2.googleapis.com/token')) {
                return Http::response(['access_token' => 'test-access-token'], 200);
            }

            $sentMessage = $request->data()['message'] ?? null;

            return Http::response(['name' => 'projects/educore-test/messages/2'], 200);
        });

        $ok = app(PushNotificationService::class)->sendToTopic(
            'educore_app_updates',
            'EduCore update available',
            'A newer build is ready.',
            ['type' => 'app_update', 'version_code' => '999'],
        );

        $this->assertTrue($ok);
        $this->assertSame('educore_app_updates', $sentMessage['topic']);
        $this->assertArrayNotHasKey('notification', $sentMessage);
        $this->assertSame('high', $sentMessage['android']['priority']);
        $this->assertSame('app_update', $sentMessage['data']['type']);
        $this->assertSame('EduCore update available', $sentMessage['data']['title']);
        $this->assertSame('A newer build is ready.', $sentMessage['data']['body']);
    }
}
