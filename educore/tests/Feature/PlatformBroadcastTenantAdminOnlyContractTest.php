<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlatformBroadcastTenantAdminOnlyContractTest extends TestCase
{
    public function test_platform_broadcast_keeps_existing_school_target_and_announcement_audience_contract(): void
    {
        $publisher = file_get_contents(app_path('Services/Notifications/PlatformBroadcastPublisher.php'));
        $view = file_get_contents(resource_path('views/super/broadcasts.blade.php'));

        $this->assertStringContainsString("'platform_broadcast_id' => $broadcastId", $publisher);
        $this->assertStringContainsString("'audience' => 'all'", $publisher);
        $this->assertStringNotContainsString("'audience' => 'tenant_admin'", $publisher);

        $this->assertStringContainsString('<label>Target Schools</label>', $view);
        $this->assertStringContainsString('All Schools', $view);
        $this->assertStringContainsString('Trial Schools Only', $view);
        $this->assertStringContainsString('Active Subscriptions Only', $view);
        $this->assertStringContainsString('Expired Schools Only', $view);
        $this->assertStringContainsString('Tenant Administrators Only', $view);
    }

    public function test_platform_push_targets_tenant_admins_by_platform_broadcast_identity(): void
    {
        $push = file_get_contents(app_path('Services/Notifications/PushNotificationService.php'));

        $this->assertStringContainsString(
            'if ($announcement->platform_broadcast_id !== null)',
            $push,
        );
        $this->assertStringContainsString(
            "$query->whereIn('role', User::roleAliasesFor('admin'))",
            $push,
        );
        $this->assertStringNotContainsString("'tenant_admin' =>", $push);

        // Existing school-level admin announcements keep their original logic.
        $this->assertStringContainsString(
            "'admin' => $query->whereIn('role', ['admin', 'principal', 'vice_principal'])",
            $push,
        );

        $this->assertStringContainsString('data-only, high-priority FCM contract', $push);
        $this->assertStringNotContainsString("'notification' =>", $push);
    }

    public function test_platform_broadcast_email_is_tenant_admin_only_without_contact_fallback(): void
    {
        $email = file_get_contents(app_path('Services/Notifications/PlatformBroadcastEmailService.php'));

        $this->assertStringContainsString("User::roleAliasesFor('admin')", $email);
        $this->assertStringNotContainsString('$tenant->email', $email);
        $this->assertStringNotContainsString("Notification::route('mail'", $email);
        $this->assertStringNotContainsString('principal', $email);
        $this->assertStringNotContainsString('vice_principal', $email);
    }

    public function test_mobile_platform_broadcast_visibility_is_admin_only_without_new_audience_value(): void
    {
        $mobile = file_get_contents(app_path('Services/Mobile/MobileCommunicationService.php'));

        $this->assertStringContainsString(
            "->when(! $user->isAdmin(), fn ($query) => $query->whereNull('platform_broadcast_id'))",
            $mobile,
        );
        $this->assertStringContainsString(
            '($announcement->platform_broadcast_id === null || $user->isAdmin())',
            $mobile,
        );
        $this->assertStringNotContainsString("'tenant_admin'", $mobile);
    }

    public function test_non_admin_web_and_portal_surfaces_exclude_platform_broadcast_announcements(): void
    {
        $announcements = file_get_contents(app_path('Http/Controllers/AnnouncementController.php'));
        $teacher = file_get_contents(app_path('Http/Controllers/Api/TeacherController.php'));
        $parent = file_get_contents(app_path('Http/Controllers/Portal/ParentPortalController.php'));
        $student = file_get_contents(app_path('Http/Controllers/Portal/StudentPortalController.php'));
        $support = file_get_contents(app_path('Http/Controllers/SupportController.php'));
        $navigation = file_get_contents(resource_path('views/layouts/partials/full-nav.blade.php'));

        $this->assertStringContainsString(
            "->when(! $user?->isAdmin(), fn ($query) => $query->whereNull('platform_broadcast_id'))",
            $announcements,
        );
        $this->assertStringContainsString("Announcement::whereNull('platform_broadcast_id')", $announcements);
        $this->assertStringContainsString("->whereNull('platform_broadcast_id')", $teacher);
        $this->assertStringContainsString("->whereNull('platform_broadcast_id')", $parent);
        $this->assertStringContainsString("->whereNull('platform_broadcast_id')", $student);

        $this->assertStringContainsString('guardTenantAdmin()', $support);
        $this->assertStringContainsString(
            "abort_unless(auth()->user()?->isAdmin(), 403, 'Tenant administrator access required.')",
            $support,
        );
        $this->assertStringContainsString(
            "@if($u->isAdmin() && $u->canAccessModule('notices'))",
            $navigation,
        );
    }

    public function test_corrective_migration_restores_standard_audience_value_for_existing_platform_broadcasts(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_09_21_125500_normalize_platform_broadcast_announcement_audience.php'));

        $this->assertStringContainsString("->whereNotNull('platform_broadcast_id')", $migration);
        $this->assertStringContainsString("->where('audience', 'tenant_admin')", $migration);
        $this->assertStringContainsString("'audience' => 'all'", $migration);
    }
}
