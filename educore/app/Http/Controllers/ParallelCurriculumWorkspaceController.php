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
            $response = $workspace->index();

            // A controller can successfully return a View object while the
            // actual Blade rendering fails later in the HTTP kernel. Force the
            // view to render inside this guarded block so Blade/template
            // exceptions are captured and surfaced by the diagnostics instead
            // of escaping as another generic 500 page.
            if ($response instanceof \Illuminate\View\View) {
                return response($response->render());
            }

            return $response;
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
