<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileOperationsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileOperationsController extends Controller
{
    /**
     * Modules whose dedicated native API contracts are present in this backend
     * generation. Older EduCore servers keep returning mobile_policy=read_first,
     * allowing the Android client to fall back to the generic synchronized
     * operations payload instead of calling routes that do not exist there.
     */
    private const NATIVE_FULL_MODULES = [
        'fees',
        'expenses',
        'payroll',
        'admissions',
        'library',
        'transport',
        'health',
        'inventory',
        'hostels',
        'subjects',
        'curriculum',
        'academic-cycle',
    ];

    public function show(Request $request, string $module, MobileOperationsService $operations): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($module === 'analytics') {
            return app(MobileAnalyticsController::class)($request);
        }

        if ($module === 'exports') {
            return app(MobileExportsController::class)($request);
        }

        if ($module === 'subjects' && $user->isStudent()) {
            return app(MobileStudentSubjectsController::class)($request);
        }

        $payload = $operations->for($user, $module);
        $normalizedModule = $module === 'parent.fees' ? 'fees' : $module;

        if (in_array($normalizedModule, self::NATIVE_FULL_MODULES, true)) {
            $payload['module']['mobile_policy'] = 'native_full';
        }

        return response()->json($payload);
    }
}
