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
                $request->query->set('compact', '1');
                $diagnosticResponse = $diagnostics($request, $service);
                $diagnosticPayload = json_decode(
                    (string) $diagnosticResponse->getContent(),
                    true
                );

                return response()->json([
                    'ok' => false,
                    'diagnostic' => 'parallel-curriculum-workspace',
                    'workspace_exception' => [
                        'exception' => get_class($e),
                        'message' => mb_substr($e->getMessage(), 0, 2000),
                        'file' => basename($e->getFile()),
                        'line' => $e->getLine(),
                    ],
                    'failed_checks' => $diagnosticPayload['failed_checks'] ?? [],
                    'primary_failure' => $diagnosticPayload['primary_failure'] ?? null,
                    'failures' => $diagnosticPayload['failures'] ?? [],
                ], 500, [], JSON_PRETTY_PRINT);
            }

            throw $e;
        }
    }
}
