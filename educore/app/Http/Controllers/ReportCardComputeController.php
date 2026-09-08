<?php

namespace App\Http\Controllers;

use App\Models\ReportCardPublication;
use App\Models\User;
use App\Services\ReportCardComputationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportCardComputeController extends Controller
{
    public function __invoke(Request $request, ReportCardComputationService $computation): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless(
            $user->isSuperAdmin() || $user->canAccessExactModule('reports'),
            403,
            'You do not have permission to compute report cards.'
        );
        abort_unless($user->tenant_id, 403);

        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'class_arm_id' => [
                'required',
                'integer',
                Rule::exists('class_arms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'term_id' => [
                'required',
                'integer',
                Rule::exists('terms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ]);

        $classArmId = (int) $data['class_arm_id'];
        $termId = (int) $data['term_id'];
        $published = ReportCardPublication::where('tenant_id', $tenantId)
            ->where('class_arm_id', $classArmId)
            ->where('term_id', $termId)
            ->where('status', 'published')
            ->exists();

        if ($published) {
            return redirect()->route('reports.preview', [
                'class_arm_id' => $classArmId,
                'term_id' => $termId,
            ])->withErrors([
                'reports' => 'These report cards are published and locked. Return them to draft before recomputing.',
            ]);
        }

        $computed = $computation->compute($tenantId, $classArmId, $termId);

        return redirect()->route('reports.preview', [
            'class_arm_id' => $classArmId,
            'term_id' => $termId,
        ])->with('success', "{$computed} report card(s) computed.");
    }
}
