<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileDashboardService;
use App\Services\Mobile\MobileModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        MobileDashboardService $dashboard,
        MobileModuleService $modules,
    ): JsonResponse {
        $payload = $dashboard->for($request);
        $user = $request->user();

        // Staff/admin Home is intentionally task-focused. Curriculum and the
        // academic lifecycle remain off Home, while personal attendance is a
        // first-class daily action. Every action is still constrained by the
        // server-authoritative module list returned for this user.
        if ($user && in_array($user->portalKey(), ['staff', 'admin'], true)) {
            $granted = collect($modules->forUser($user))->keyBy(
                fn (array $module): string => strtolower($module['key'])
            );

            $preferred = [
                'students',
                'staff',
                'classes',
                'subjects',
                'staff-attendance.self',
            ];

            $payload['quick_actions'] = collect($preferred)
                ->map(fn (string $key) => $granted->get($key))
                ->filter()
                ->map(fn (array $module): array => [
                    'module_key' => $module['key'],
                    'title' => $module['key'] === 'staff-attendance.self' ? 'My Attendance' : $module['title'],
                    'icon' => $module['icon'],
                    'path' => $module['path'],
                ])
                ->values()
                ->all();
        }

        return response()->json($payload);
    }
}
