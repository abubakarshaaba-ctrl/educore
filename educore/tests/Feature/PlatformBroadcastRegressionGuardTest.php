<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlatformBroadcastRegressionGuardTest extends TestCase
{
    public function test_active_web_and_api_publishers_use_the_same_after_response_pipeline(): void
    {
        $web = file_get_contents(app_path('Http/Controllers/WebPlatformBroadcastController.php'));
        $api = file_get_contents(app_path('Http/Controllers/Api/PlatformBroadcastController.php'));
        $publisher = file_get_contents(app_path('Services/Notifications/PlatformBroadcastPublisher.php'));
        $job = file_get_contents(app_path('Jobs/DeliverPlatformBroadcastAfterResponse.php'));

        $this->assertStringContainsString('PlatformBroadcastPublisher', $web);
        $this->assertStringContainsString('PlatformBroadcastPublisher', $api);
        $this->assertStringNotContainsString('extends PlatformBroadcastController', $web);
        $this->assertStringContainsString('DeliverPlatformBroadcastAfterResponse::dispatchAfterResponse(', $publisher);
        $this->assertStringContainsString('PushNotificationService', $job);
        $this->assertStringContainsString('PlatformBroadcastEmailService', $job);
        $this->assertStringContainsString("Log::info('Platform broadcast delivery completed.'", $job);
    }

    public function test_platform_broadcast_email_uses_the_dedicated_compact_template(): void
    {
        $notification = file_get_contents(app_path('Notifications/Tenant/PlatformBroadcastNotification.php'));
        $view = file_get_contents(resource_path('views/mail/platform/broadcast.blade.php'));

        $this->assertStringContainsString("'mail.platform.broadcast'", $notification);
        $this->assertStringContainsString('EduCore Platform Broadcast — ', $notification);
        $this->assertStringNotContainsString('->line($this->title)', $notification);
        $this->assertStringContainsString('Platform Broadcast', $view);
        $this->assertStringContainsString('{{ $broadcastTitle }}', $view);
        $this->assertStringContainsString('A new EduCore platform broadcast has been sent', $view);
        $this->assertStringContainsString('View Platform Notices', $view);
    }

    public function test_native_push_contract_remains_data_only_and_the_legacy_listener_stays_removed(): void
    {
        $push = file_get_contents(app_path('Services/Notifications/PushNotificationService.php'));

        $this->assertStringContainsString('data-only, high-priority FCM contract', $push);
        $this->assertStringContainsString("'android' => [", $push);
        $this->assertStringContainsString("'priority' => 'high'", $push);
        $this->assertStringNotContainsString("'notification' =>", $push);
        $this->assertFileDoesNotExist(app_path('Services/Notifications/PlatformBroadcastPushListener.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Api/MobilePlatformBroadcastController.php'));

        $legacy = file_get_contents(app_path('Http/Controllers/SuperAdminController.php'));
        $this->assertStringNotContainsString('function storeBroadcast(', $legacy);
    }

    public function test_delivery_transport_failures_are_contained_after_publication(): void
    {
        $job = file_get_contents(app_path('Jobs/DeliverPlatformBroadcastAfterResponse.php'));

        $this->assertStringContainsString('catch (Throwable $error)', $job);
        $this->assertStringContainsString('Platform broadcast push delivery failed for an announcement.', $job);
        $this->assertStringContainsString('Platform broadcast email delivery failed before recipient completion.', $job);
    }
}
