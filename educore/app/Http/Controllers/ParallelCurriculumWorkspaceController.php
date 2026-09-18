<?php

namespace App\Http\Controllers;

use App\Services\ParallelCurriculumService;
use Illuminate\Http\Request;

class ParallelCurriculumWorkspaceController extends Controller
{
    public function __invoke(
        Request $request,
        ParallelCurriculumController $workspace,
        ParallelCurriculumDiagnosticsController $diagnostics,
        ParallelCurriculumService $service
    ) {
        try {
            return $workspace->index();
        } catch (\Throwable $e) {
            report($e);

            $user = $request->user();
            if (
                $user
                && ($user->isSuperAdmin() || $user->canAccessExactModule('scores'))
            ) {
                return $diagnostics($request, $service);
            }

            throw $e;
        }
    }
}
