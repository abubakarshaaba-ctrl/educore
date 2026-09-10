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

        $payload = $operations->for($user, $module);
        $normalized = $module === 'parent.fees' ? 'fees' : $module;

        // The Android app already contains dedicated transactional workspaces
        // for these finance modules. Only advertise them when the server says
        // this account can actually manage the module; parents remain read-only.
        if (
            ! $user->isParent()
            && in_array($normalized, ['fees', 'expenses', 'payroll'], true)
            && (bool) data_get($payload, 'module.can_manage', false)
        ) {
            $payload['module']['mobile_policy'] = 'native_full';
        }

        return response()->json($payload);
    }
}
