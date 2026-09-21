<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\MobileReleaseController;
use App\Http\Controllers\SelfDeployController;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class MobileReleasePushRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config([
            'services.mobile_release.webhook_token' => 'test-release-secret',
            'app.url' => 'https://educoreng.online',
        ]);
    }

    public function test_release_webhook_retries_until_push_delivery_is_confirmed(): void
    {
        $failedPush = Mockery::mock(PushNotificationService::class);
        $failedPush->shouldReceive('sendToTopic')->once()->andReturnFalse();

        $first = (new MobileReleaseController())($this->releaseRequest(), $failedPush);

        $this->assertSame(502, $first->status());
        $this->assertSame('failed', $first->getData(true)['push']);
        $this->assertTrue($first->getData(true)['retryable']);

        $release = $this->storedRelease();
        $this->assertNull($release['push_sent_at']);

        $successfulPush = Mockery::mock(PushNotificationService::class);
        $successfulPush->shouldReceive('sendToTopic')->once()->andReturnTrue();

        $second = (new MobileReleaseController())($this->releaseRequest(), $successfulPush);

        $this->assertSame(200, $second->status());
        $this->assertSame('sent', $second->getData(true)['push']);
        $this->assertFalse($second->getData(true)['retryable']);
        $this->assertNotEmpty($this->storedRelease()['push_sent_at']);

        $alreadySent = Mockery::mock(PushNotificationService::class);
        $alreadySent->shouldNotReceive('sendToTopic');

        $third = (new MobileReleaseController())($this->releaseRequest(), $alreadySent);

        $this->assertSame(200, $third->status());
        $this->assertSame('skipped-already-notified', $third->getData(true)['push']);
    }

    public function test_self_deploy_retries_same_release_after_a_failed_push(): void
    {
        $controller = new SelfDeployController();
        $method = (new ReflectionClass($controller))->getMethod('recordAndNotifyMobileRelease');

        $apk = [
            'status' => 'updated',
            'release' => 'android-v2.0.0-alpha99-code999-b1-abcdef123456',
            'sha256' => str_repeat('a', 64),
        ];

        $failedPush = Mockery::mock(PushNotificationService::class);
        $failedPush->shouldReceive('sendToTopic')->once()->andReturnFalse();

        $first = $method->invoke($controller, $apk, $failedPush);

        $this->assertSame('failed', $first['push']);
        $this->assertTrue($first['retryable']);
        $this->assertNull($this->storedRelease()['push_sent_at']);

        $successfulPush = Mockery::mock(PushNotificationService::class);
        $successfulPush->shouldReceive('sendToTopic')->once()->andReturnTrue();

        $second = $method->invoke($controller, $apk, $successfulPush);

        $this->assertSame('sent', $second['push']);
        $this->assertFalse($second['retryable']);
        $this->assertNotEmpty($this->storedRelease()['push_sent_at']);

        $alreadySent = Mockery::mock(PushNotificationService::class);
        $alreadySent->shouldNotReceive('sendToTopic');

        $third = $method->invoke($controller, $apk, $alreadySent);

        $this->assertSame('skipped-already-notified', $third['push']);
    }

    private function releaseRequest(): Request
    {
        return Request::create(
            '/api/v1/mobile-release/notify',
            'POST',
            [
                'version_name' => '2.0.0-alpha99',
                'version_code' => 999,
                'download_url' => 'https://example.test/EduCore.apk',
                'message' => 'A newer EduCore build is ready.',
                'force_update' => false,
                'sha256' => str_repeat('a', 64),
                'source_release_tag' => 'android-v2.0.0-alpha99-code999-b1-abcdef123456',
            ],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer test-release-secret',
                'HTTP_ACCEPT' => 'application/json',
            ],
        );
    }

    private function storedRelease(): array
    {
        return json_decode(
            (string) Storage::disk('local')->get('mobile-releases/android.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
