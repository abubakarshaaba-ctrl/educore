<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileOperationsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileOperationsController extends Controller
{
    /**
     * This convergence branch only advertises dedicated native mutations for
     * modules whose contracts have actually been ported to the live backend.
     * Every other module remains read_first and is rendered from the generic,
     * tenant-scoped operations payload by compatible Android builds.
     */
    private const NATIVE_FULL_MODULES = [
        'admissions',
        'transport',
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
