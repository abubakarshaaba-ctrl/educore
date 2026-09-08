<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileSubjectsController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $tenantId = (int) $user->tenant_id;
        $search = trim((string) ($data['q'] ?? ''));
        $status = (string) ($data['status'] ?? 'all');
        $perPage = (int) ($data['per_page'] ?? 30);
        $base = Subject::where('tenant_id', $tenantId);

        $subjects = (clone $base)
            ->withCount(['classLevelRules', 'classArms', 'scores', 'studentSelections'])
            ->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'contract_version' => 1,
            'capabilities' => ['manage' => $user->canManage('subjects')],
            'metrics' => [
                'total' => (clone $base)->count(),
                'active' => (clone $base)->where('is_active', true)->count(),
                'inactive' => (clone $base)->where('is_active', false)->count(),
            ],
            'status_options' => [
                ['key' => 'all', 'label' => 'All'],
                ['key' => 'active', 'label' => 'Active'],
                ['key' => 'inactive', 'label' => 'Inactive'],
            ],
            'subjects' => collect($subjects->items())->map(fn (Subject $subject): array => $this->payload($subject)),
            'selected' => ['search' => $search, 'status' => $status],
            'meta' => [
                'page' => $subjects->currentPage(),
                'per_page' => $subjects->perPage(),
                'total' => $subjects->total(),
                'last_page' => $subjects->lastPage(),
                'has_more' => $subjects->hasMorePages(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->guard($request, manage: true);
        $data = $this->validatedSubject($request, $user);
        $subject = Subject::create($data + ['tenant_id' => $user->tenant_id]);
        $this->loadCounts($subject);

        return response()->json([
            'message' => 'Subject created.',
            'subject' => $this->payload($subject),
        ], 201);
    }

    public function update(Request $request, int $subject)
    {
        $user = $this->guard($request, manage: true);
        $record = Subject::where('tenant_id', $user->tenant_id)->findOrFail($subject);
        $data = $this->validatedSubject($request, $user, $record->id);
        $record->update($data);
        $record->refresh();
        $this->loadCounts($record);

        return response()->json([
            'message' => 'Subject updated.',
            'subject' => $this->payload($record),
        ]);
    }

    public function destroy(Request $request, int $subject)
    {
        $user = $this->guard($request, manage: true);
        $record = Subject::where('tenant_id', $user->tenant_id)->findOrFail($subject);
        $this->loadCounts($record);

        $references = [
            'class assignments' => (int) $record->class_arms_count,
            'curriculum rules' => (int) $record->class_level_rules_count,
            'scores' => (int) $record->scores_count,
            'student selections' => (int) $record->student_selections_count,
        ];
        $usedBy = collect($references)->filter(fn (int $count): bool => $count > 0);
        if ($usedBy->isNotEmpty()) {
            throw ValidationException::withMessages([
                'subject' => 'This subject is in use and cannot be deleted. Deactivate it instead. References: '
                    .$usedBy->map(fn (int $count, string $label): string => "{$label} ({$count})")->implode(', ').'.',
            ]);
        }

        $record->delete();

        return response()->json(['message' => 'Subject deleted.']);
    }

    private function validatedSubject(Request $request, User $user, ?int $ignoreId = null): array
    {
        $tenantId = (int) $user->tenant_id;
        $nameRule = Rule::unique('subjects', 'name')
            ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at'));
        $codeRule = Rule::unique('subjects', 'code')
            ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at'));
        if ($ignoreId !== null) {
            $nameRule->ignore($ignoreId);
            $codeRule->ignore($ignoreId);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', $nameRule],
            'code' => ['nullable', 'string', 'max:10', $codeRule],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['name'] = trim($data['name']);
        $data['code'] = isset($data['code']) && trim((string) $data['code']) !== ''
            ? strtoupper(trim((string) $data['code']))
            : null;

        return $data;
    }

    private function loadCounts(Subject $subject): void
    {
        $subject->loadCount(['classLevelRules', 'classArms', 'scores', 'studentSelections']);
    }

    private function payload(Subject $subject): array
    {
        return [
            'id' => $subject->id,
            'name' => $subject->name,
            'code' => $subject->code,
            'active' => (bool) $subject->is_active,
            'references' => [
                'class_assignments' => (int) ($subject->class_arms_count ?? 0),
                'curriculum_rules' => (int) ($subject->class_level_rules_count ?? 0),
                'scores' => (int) ($subject->scores_count ?? 0),
                'student_selections' => (int) ($subject->student_selections_count ?? 0),
            ],
        ];
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School subject access required.');
        abort_unless($user->tenant_id, 403, 'School subject access required.');
        abort_unless(
            $manage ? $user->canManage('subjects') : $user->canAccessModule('subjects'),
            403,
            $manage ? 'Subject management permission required.' : 'Subject access required.'
        );

        return $user;
    }
}
