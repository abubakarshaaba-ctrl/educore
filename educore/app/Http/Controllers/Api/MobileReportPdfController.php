<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReportCardController;
use App\Models\TermlySummary;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileReportPdfController extends Controller
{
    public function __invoke(Request $request, int $summary, ReportCardController $renderer): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403);
        abort_unless($user->tenant_id, 403);
        abort_unless(
            $user->canAccessExactModule('reports'),
            403,
            'Full report-card access is required to download this PDF.'
        );

        $tenantId = (int) $user->tenant_id;
        $record = TermlySummary::where('tenant_id', $tenantId)
            ->whereKey($summary)
            ->with('student')
            ->firstOrFail();

        $student = $record->student;
        abort_unless($student && (int) $student->tenant_id === $tenantId, 404);

        // The legacy web renderer derives its class from the Student model.
        // Bind the in-memory model to the class stored on this historical
        // summary so downloading an older report cannot silently render the
        // student's present-day class. This does not persist any student data.
        $student->forceFill(['current_class_arm_id' => $record->class_arm_id]);
        $request->merge(['term_id' => $record->term_id]);

        return $renderer->pdf($request, $student);
    }
}
