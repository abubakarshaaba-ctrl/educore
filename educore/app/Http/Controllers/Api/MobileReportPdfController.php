<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TermlySummary;
use App\Models\User;
use App\Services\ReportCardDocumentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileReportPdfController extends Controller
{
    public function __invoke(Request $request, int $summary, ReportCardDocumentService $documents): Response
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
            ->firstOrFail();

        return $documents->download($tenantId, $record->id);
    }
}
