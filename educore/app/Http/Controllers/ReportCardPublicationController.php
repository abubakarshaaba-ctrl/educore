<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ReportCardPublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ReportCardPublicationController extends Controller
{
    public function publish(Request $request, ReportCardPublicationService $publication): RedirectResponse
    {
        $user = $this->authorizedUser($request);
        $tenantId = (int) $user->tenant_id;
        $data = $this->validateSelection($request, $tenantId, includeNote: true);
        $classArmIds = $this->classArmIds($data);
        $termId = (int) $data['term_id'];

        $result = $publication->publishMany(
            $tenantId,
            $classArmIds->all(),
            $termId,
            $user,
            isset($data['note']) ? (string) $data['note'] : null,
            $request,
        );

        $message = $classArmIds->count() === 1
            ? 'Report cards published. Parents can now view results.'
            : $classArmIds->count().' classes published. Parents can now view results.';
        if ($result['guardians_notified'] > 0) {
            $message .= " {$result['guardians_notified']} guardian notification(s) queued/sent.";
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

        $publication->unpublishMany(
            $tenantId,
            $classArmIds->all(),
            $termId,
            $user,
            $request,
        );

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
        $rules = [
            'term_id' => [
                'required',
                'integer',
                Rule::exists('terms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'class_arm_id' => [
                'required_without:class_arm_ids',
                'nullable',
                'integer',
                Rule::exists('class_arms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'class_arm_ids' => ['required_without:class_arm_id', 'nullable', 'array', 'min:1'],
            'class_arm_ids.*' => [
                'integer',
                Rule::exists('class_arms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ];
        if ($includeNote) {
            $rules['note'] = ['nullable', 'string', 'max:2000'];
        }

        return $request->validate($rules);
    }

    private function classArmIds(array $data): Collection
    {
        return collect($data['class_arm_ids'] ?? [$data['class_arm_id']])
            ->filter(fn ($value): bool => $value !== null)
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values();
    }
}
