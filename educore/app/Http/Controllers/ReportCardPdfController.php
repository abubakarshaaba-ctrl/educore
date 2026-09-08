<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\TermlySummary;
use App\Models\User;
use App\Services\ReportCardDocumentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ReportCardPdfController extends Controller
{
    public function __invoke(
        Request $request,
        Student $student,
        ReportCardDocumentService $documents,
    ): Response {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($user->tenant_id, 403);
        abort_unless(
            $user->isSuperAdmin() || $user->canAccessExactModule('reports'),
            403,
            'Full report-card access is required to download this PDF.'
        );

        $tenantId = (int) $user->tenant_id;
        abort_unless((int) $student->tenant_id === $tenantId, 404);
        $data = $request->validate([
            'term_id' => [
                'required',
                'integer',
                Rule::exists('terms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'class_arm_id' => [
                'nullable',
                'integer',
                Rule::exists('class_arms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ]);

        $summary = TermlySummary::where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->where('term_id', (int) $data['term_id'])
            ->when(
                isset($data['class_arm_id']),
                fn ($query) => $query->where('class_arm_id', (int) $data['class_arm_id'])
            )
            ->orderByDesc('computed_at')
            ->orderByDesc('id')
            ->firstOrFail();

        return $documents->download($tenantId, $summary->id);
    }
}
