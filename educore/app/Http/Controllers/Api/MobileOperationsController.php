<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileOperationsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileOperationsController extends Controller
{
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

        return response()->json($operations->for($user, $module));
    }
}
