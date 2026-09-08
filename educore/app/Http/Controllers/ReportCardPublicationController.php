<?php

namespace App\Http\Controllers;

use App\Models\ReportCardPublication;
use App\Models\TermlySummary;
use App\Models\User;
use App\Services\ReportCardPublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportCardPublicationController extends Controller
{
    public function publish(Request $request, ReportCardPublicationService $publication): RedirectResponse
    {
        $user = $this->authorizedUser($request);
        $tenantId = (int) $user->tenant_id;
        $data = $this->validateSelection($request, $tenantId, includeNote: true);
        $classArmIds = $this->classArmIds($data);
        $termId = (int) $data['term_id'];

        $computedCounts = TermlySummary::where('tenant_id', $tenantId)
            ->where('term_id', $termId)
            ->whereIn('class_arm_id', $classArmIds)
            ->selectRaw('class_arm_id, COUNT(*) as aggregate')
            ->groupBy('class_arm_id')
            ->pluck('aggregate', 'class_arm_id');

        $missing = $classArmIds->first(fn (int $classArmId): bool => (int) ($computedCounts[$classArmId] ?? 0) < 1);
        if ($missing !== null) {
            throw ValidationException::withMessages([
                'class_arm_ids' => 'Compute report cards for every selected class before publishing.',
            ]);
        }

        $alreadyPublished = ReportCardPublication::where('tenant_id', $tenantId)
            ->where('term_id', $termId)
            ->whereIn('class_arm_id', $classArmIds)
            ->where('status', 'published')
            ->exists();
        if ($alreadyPublished) {
            throw ValidationException::withMessages([
                'class_arm_ids' => 'One or more selected classes are already published.',
            ]);
        }

        $notified = 0;
        foreach ($classArmIds as $classArmId) {
            $result = $publication->publish(
                $tenantId,
                $classArmId,
                $termId,
                $user,
                isset($data['note']) ? (string) $data['note'] : null,
                $request,
            );
            $notified += $result['guardians_notified'];
        }

        $message = $classArmIds->count() === 1
            ? 'Report cards published. Parents can now view results.'
            : $classArmIds->count().' classes published. Parents can now view results.';
        if ($notified > 0) {
            $message .= " {$notified} guardian notification(s) queued/sent.";
        }

        return redirect()->route('reports.publications', ['term_id' => $termId])
            ->with('success', $message);
    }

    public function unpublish(Request $request, ReportCardPublicationService $publication): RedirectResponse
    {
        $user = $this->authorizedUser($request);
        $tenantId = (int) $user->tenant_id;
        $data = $this->validateSelection($request, $tenantId);
        $classArmIds = $this->classArmIds($data);
        $termId = (int) $data['term_id'];

        $publishedCount = ReportCardPublication::where('tenant_id', $tenantId)
            ->where('term_id', $termId)
            ->whereIn('class_arm_id', $classArmIds)
            ->where('status', 'published')
            ->count();
        if ($publishedCount !== $classArmIds->count()) {
            throw ValidationException::withMessages([
                'class_arm_ids' => 'Every selected class must be published before it can be returned to draft.',
            ]);
        }

        foreach ($classArmIds as $classArmId) {
            $publication->unpublish(
                $tenantId,
                $classArmId,
                $termId,
                $user,
                $request,
            );
        }

        $message = $classArmIds->count() === 1
            ? 'Report cards unpublished (set back to draft).'
            : $classArmIds->count().' classes unpublished.';

        return redirect()->route('reports.publications', ['term_id' => $termId])
            ->with('success', $message);
    }

    private function authorizedUser(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($user->tenant_id, 403);
        abort_unless(
            $user->isSuperAdmin() || $user->canAccessExactModule('reports'),
            403,
            'You do not have permission to manage report cards.'
        );
        abort_unless(
            $user->isSuperAdmin() || $user->canAccessExactModule('students'),
            403,
            'Only academic administrators can publish or unpublish report cards.'
        );

        return $user;
    }

    private function validateSelection(Request $request, int $tenantId, bool $includeNote = false): array
    {
        $classRule = Rule::exists('class_arms', 'id')
            ->where(fn ($query) => $query->where('tenant_id', $tenantId));
        $rules = [
            'term_id' => [
                'required',
                'integer',
                Rule::exists('terms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'class_arm_id' => ['required_without:class_arm_ids', 'nullable', 'integer', $classRule],
            'class_arm_ids' => ['required_without:class_arm_id', 'nullable', 'array', 'min:1'],
            'class_arm_ids.*' => ['integer', $classRule],
        ];
        if ($includeNote) {
            $rules['note'] = ['nullable', 'string', 'max:2000'];
        }

        return $request->validate($rules);
    }

    private function classArmIds(array $data)
    {
        return collect($data['class_arm_ids'] ?? [$data['class_arm_id']])
            ->filter(fn ($value): bool => $value !== null)
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values();
    }
}
