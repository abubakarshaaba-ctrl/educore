<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    public function __invoke(Request $request, MobileDashboardService $dashboard): JsonResponse
    {
        return response()->json($dashboard->for($request));
    }
}
