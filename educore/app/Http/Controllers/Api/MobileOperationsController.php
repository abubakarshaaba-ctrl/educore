<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileOperationsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileOperationsController extends Controller
{
    public function show(Request $request, string $module, MobileOperationsService $operations): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($module === 'analytics') {
            return app(MobileAnalyticsController::class)($request);
        }

        return response()->json($operations->for($user, $module));
    }
}
