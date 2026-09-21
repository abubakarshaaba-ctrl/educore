<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlatformBroadcastTenantAdminOnlyContractTest extends TestCase
{
    public function test_platform_broadcast_publication_uses_internal_tenant_admin_audience(): void
    {
        $publisher = file_get_contents(app_path('Services/Notifications/PlatformBroadcastPublisher.php'));

        $this->assertStringContainsString("'audience' => 'tenant_admin'", $publisher);
        $this->assertStringNotContainsString("'audience' => 'all'", $publisher);
    }

    public function test_platform_push_targets_exact_tenant_admin_role_aliases(): void
    {
        $push = file_get_contents(app_path('Services/Notifications/PushNotificationService.php'));

        $this->assertStringContainsString(
            "'tenant_admin' => $query->whereIn('role', User::roleAliasesFor('admin'))",
            $push,
        );

        // Existing school-level "admin" announcement semantics may still
        // include school leadership; platform broadcasts use tenant_admin.
        $this->assertStringContainsString(
            "'admin' => $query->whereIn('role', ['admin', 'principal', 'vice_principal'])",
            $push,
        );

        $this->assertStringContainsString('data-only, high-priority FCM contract', $push);
        $this->assertStringNotContainsString("'notification' =>", $push);
    }

    public function test_platform_broadcast_email_has_no_non_admin_fallback(): void
    {
        $email = file_get_contents(app_path('Services/Notifications/PlatformBroadcastEmailService.php'));

        $this->assertStringContainsString("User::roleAliasesFor('admin')", $email);
        $this->assertStringNotContainsString('$tenant->email', $email);
        $this->assertStringNotContainsString("Notification::route('mail'", $email);
        $this->assertStringNotContainsString('principal', $email);
        $this->assertStringNotContainsString('vice_principal', $email);
    }

    public function test_mobile_notification_feed_exposes_platform_broadcasts_only_to_tenant_admins(): void
    {
        $mobile = file_get_contents(app_path('Services/Mobile/MobileCommunicationService.php'));

        $this->assertStringContainsString(
            "\$user->isAdmin() => ['all', 'staff', 'admin', 'tenant_admin']",
            $mobile,
        );
        $this->assertStringContainsString(
            "\$user->canManage('announcements') => ['all', 'staff', 'admin']",
            $mobile,
        );
    }

    public function test_web_and_platform_notices_routes_hide_platform_broadcasts_from_non_admins(): void
    {
        $announcements = file_get_contents(app_path('Http/Controllers/AnnouncementController.php'));
        $support = file_get_contents(app_path('Http/Controllers/SupportController.php'));
        $navigation = file_get_contents(resource_path('views/layouts/partials/full-nav.blade.php'));

        $this->assertStringContainsString(
            "->when(! \$user?->isAdmin(), fn (\$query) => \$query->whereNull('platform_broadcast_id'))",
            $announcements,
        );
        $this->assertStringContainsString("Announcement::whereNull('platform_broadcast_id')", $announcements);
        $this->assertStringContainsString('guardTenantAdmin()', $support);
        $this->assertStringContainsString(
            "abort_unless(auth()->user()?->isAdmin(), 403, 'Tenant administrator access required.')",
            $support,
        );
        $this->assertStringContainsString(
            "@if(\$u->isAdmin() && \$u->canAccessModule('notices'))",
            $navigation,
        );
    }

    public function test_existing_platform_announcements_are_reclassified_on_deploy(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_09_21_113500_restrict_platform_broadcasts_to_tenant_admins.php'));

        $this->assertStringContainsString("->whereNotNull('platform_broadcast_id')", $migration);
        $this->assertStringContainsString("'audience' => 'tenant_admin'", $migration);
    }
}
