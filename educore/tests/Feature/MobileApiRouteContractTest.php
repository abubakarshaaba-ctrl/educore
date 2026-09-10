<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MobileApiRouteContractTest extends TestCase
{
    public function test_native_android_api_routes_are_registered(): void
    {
        $registered = collect(Route::getRoutes())->flatMap(function ($route) {
            return collect($route->methods())->map(fn (string $method): string => $method.' '.$route->uri());
        })->all();

        $expected = [
            // Core mobile shell.
            'GET api/v1/bootstrap',
            'GET api/v1/dashboard',
            'GET api/v1/operations/{module}',

            // Academic and operations workspaces.
            'GET api/v1/academic-cycle',
            'GET api/v1/fees',
            'GET api/v1/expenses',
            'GET api/v1/hostels',
            'GET api/v1/inventory',
            'GET api/v1/subjects',
            'GET api/v1/curriculum',
            'GET api/v1/admissions',
            'GET api/v1/transport-officer/dashboard',
            'GET api/v1/health-officer/dashboard',

            // Administrator Staff Attendance. These supplemental routes are
            // required by the native app and must never silently disappear.
            'GET api/v1/admin/staff-attendance',
            'GET api/v1/admin/staff-attendance/report',
            'POST api/v1/admin/staff-attendance/manual',
            'GET api/v1/admin/staff-attendance/offline',
            'POST api/v1/admin/staff-attendance/offline/{record}',
            'GET api/v1/admin/staff-attendance/proxy-reviews',
            'POST api/v1/admin/staff-attendance/proxy-reviews/{record}',
            'GET api/v1/admin/staff-attendance/qr',
            'POST api/v1/admin/staff-attendance/reset-qr',
            'PUT api/v1/admin/staff-attendance/settings',

            // Personal attendance remains a separate self-service contract.
            'GET api/v1/staff-attendance',
            'POST api/v1/staff-attendance/clock-in',
            'POST api/v1/staff-attendance/clock-out',

            // Staff CBT management. Both the read and authoring contracts are
            // intentionally asserted because the Android UI depends on them.
            'GET api/v1/staff/cbt/options',
            'POST api/v1/staff/cbt/exams',
            'GET api/v1/staff/cbt/exams',
            'GET api/v1/staff/cbt/exams/{exam}',
            'POST api/v1/staff/cbt/exams/{exam}/publish',
            'POST api/v1/staff/cbt/exams/{exam}/close',
            'PATCH api/v1/staff/cbt/exams/{exam}/schedule',

            // Inbox / communication contracts.
            'GET api/v1/messages',
            'GET api/v1/messages/recipients',
            'POST api/v1/messages',
            'GET api/v1/messages/{thread}',
            'POST api/v1/messages/{thread}/reply',
            'GET api/v1/notifications',
            'POST api/v1/notifications/read-all',
            'POST api/v1/notifications/{announcement}/read',
            'GET api/v1/calendar/events',

            // Platform/support workspaces.
            'GET api/v1/platform/dashboard',
            'GET api/v1/platform/plans',
            'GET api/v1/platform/analytics',
            'GET api/v1/platform/support',
            'GET api/v1/platform/broadcasts',
            'GET api/v1/platform/settings',
            'GET api/v1/platform/gateways',
            'GET api/v1/reports',
            'GET api/v1/portal-accounts',
        ];

        foreach ($expected as $signature) {
            $this->assertContains($signature, $registered, "Missing native API route: {$signature}");
        }
    }
}
