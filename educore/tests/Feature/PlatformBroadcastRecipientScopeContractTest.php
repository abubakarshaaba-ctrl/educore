<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlatformBroadcastRecipientScopeContractTest extends TestCase
{
    public function test_super_admin_form_has_independent_school_and_recipient_dropdowns(): void
    {
        $view = file_get_contents(resource_path('views/super/broadcasts.blade.php'));

        $this->assertStringContainsString('<label>Target Schools</label>', $view);
        $this->assertStringContainsString('name="target"', $view);
        $this->assertStringContainsString('All Schools', $view);
        $this->assertStringContainsString('Trial Schools Only', $view);
        $this->assertStringContainsString('Active Subscriptions Only', $view);
        $this->assertStringContainsString('Expired Schools Only', $view);

        $this->assertStringContainsString('<label>Recipients</label>', $view);
        $this->assertStringContainsString('name="recipient_scope"', $view);
        $this->assertStringContainsString('value="tenant_admin"', $view);
        $this->assertStringContainsString('Tenant Administrators Only', $view);
        $this->assertStringContainsString('value="all_users"', $view);
        $this->assertStringContainsString('All Users', $view);
    }

    public function test_publisher_persists_recipient_scope_and_maps_it_to_announcement_visibility(): void
    {
        $publisher = file_get_contents(app_path('Services/Notifications/PlatformBroadcastPublisher.php'));

        $this->assertStringContainsString(
            "'recipient_scope' => ['nullable', Rule::in(['tenant_admin', 'all_users'])]",
            $publisher,
        );
        $this->assertStringContainsString(
            "$recipientScope = (string) ($data['recipient_scope'] ?? 'tenant_admin')",
            $publisher,
        );
        $this->assertStringContainsString("'recipient_scope' => $recipientScope", $publisher);
        $this->assertStringContainsString(
            "'audience' => $recipientScope === 'all_users' ? 'all' : 'tenant_admin'",
            $publisher,
        );
    }

    public function test_push_delivery_supports_both_recipient_modes_without_changing_fcm_transport(): void
    {
        $push = file_get_contents(app_path('Services/Notifications/PushNotificationService.php'));

        $this->assertStringContainsString(
            "'tenant_admin' => $query->whereIn('role', User::roleAliasesFor('admin'))",
            $push,
        );
        $this->assertStringContainsString('default => $query', $push);

        $this->assertStringContainsString('data-only, high-priority FCM contract', $push);
        $this->assertStringContainsString("'priority' => 'high'", $push);
        $this->assertStringNotContainsString("'notification' =>", $push);
    }

    public function test_email_delivery_switches_between_tenant_admins_and_all_active_users(): void
    {
        $service = file_get_contents(app_path('Services/Notifications/PlatformBroadcastEmailService.php'));
        $job = file_get_contents(app_path('Jobs/DeliverPlatformBroadcastAfterResponse.php'));

        $this->assertStringContainsString("string $recipientScope = 'tenant_admin'", $service);
        $this->assertStringContainsString(
            "$recipientScope === 'tenant_admin'",
            $service,
        );
        $this->assertStringContainsString("User::roleAliasesFor('admin')", $service);
        $this->assertStringContainsString("->where('is_active', true)", $service);
        $this->assertStringContainsString("->where('is_super_admin', false)", $service);
        $this->assertStringContainsString(
            "recipientScope: (string) ($broadcast->recipient_scope ?? 'tenant_admin')",
            $job,
        );
    }

    public function test_in_app_visibility_supports_admin_only_and_all_user_platform_broadcasts(): void
    {
        $mobile = file_get_contents(app_path('Services/Mobile/MobileCommunicationService.php'));
        $web = file_get_contents(app_path('Http/Controllers/AnnouncementController.php'));
        $teacher = file_get_contents(app_path('Http/Controllers/Api/TeacherController.php'));
        $parent = file_get_contents(app_path('Http/Controllers/Portal/ParentPortalController.php'));
        $student = file_get_contents(app_path('Http/Controllers/Portal/StudentPortalController.php'));

        $this->assertStringContainsString(
            "$user->isAdmin() => ['all', 'staff', 'admin', 'tenant_admin']",
            $mobile,
        );
        $this->assertStringContainsString(
            "$user->canManage('announcements') => ['all', 'staff', 'admin']",
            $mobile,
        );

        $this->assertStringContainsString("->whereNotNull('platform_broadcast_id')", $web);
        $this->assertStringContainsString("->where('audience', 'all')", $web);

        $this->assertStringNotContainsString("->whereNull('platform_broadcast_id')", $teacher);
        $this->assertStringNotContainsString("->whereNull('platform_broadcast_id')", $parent);
        $this->assertStringNotContainsString("->whereNull('platform_broadcast_id')", $student);
    }

    public function test_recipient_scope_migration_defaults_existing_broadcasts_to_tenant_admins(): void
    {
        $migration = file_get_contents(
            database_path('migrations/2026_09_21_130500_add_recipient_scope_to_platform_broadcasts.php')
        );

        $this->assertStringContainsString(
            "->default('tenant_admin')",
            $migration,
        );
        $this->assertStringContainsString(
            "'audience' => 'tenant_admin'",
            $migration,
        );
    }

    public function test_api_exposes_recipient_scope(): void
    {
        $api = file_get_contents(app_path('Http/Controllers/Api/PlatformBroadcastController.php'));

        $this->assertStringContainsString(
            "'recipient_scope' => $broadcast->recipient_scope ?? 'tenant_admin'",
            $api,
        );
        $this->assertStringContainsString(
            "'recipient_scope' => $publication['recipient_scope']",
            $api,
        );
    }
}
