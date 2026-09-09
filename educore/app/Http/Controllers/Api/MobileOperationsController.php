<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileOperationsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileOperationsController extends Controller
{
    /**
     * Only advertise dedicated native mutations for contracts that have been
     * converged onto this production-compatible backend generation.
     */
    private const NATIVE_FULL_MODULES = [
        'admissions',
        'transport',
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

        $payload = $operations->for($user, $module);
        $normalizedModule = $module === 'parent.fees' ? 'fees' : $module;

        if (in_array($normalizedModule, self::NATIVE_FULL_MODULES, true)) {
            $payload['module']['mobile_policy'] = 'native_full';
        }

        return response()->json($payload);
    }
}
