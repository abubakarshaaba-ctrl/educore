<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileStaffDirectoryController extends Controller
{
    private const ROLES = [
        'admin', 'principal', 'head', 'head_teacher',
        'vice_principal', 'academic_administrator',
    ];

    public function __invoke(Request $request)
    {
        $user = $this->administrator($request);

        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = trim((string) ($validated['query'] ?? ''));
        $status = $validated['status'] ?? 'all';
        $perPage = (int) ($validated['per_page'] ?? 50);

        // School heads are explicitly allowed to use the native staff directory.
        // Keep the projection tenant-scoped and exclude non-staff portal accounts.
        $base = User::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereNotIn('role', ['student', 'parent', 'super_admin']);

        $counts = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
        ];

        $staffQuery = clone $base;

        if ($query !== '') {
            $staffQuery->where(function ($builder) use ($query) {
                $like = '%'.$query.'%';
                $builder->where('name', 'like', $like)
                    ->orWhere('staff_id', 'like', $like)
                    ->orWhere('role', 'like', $like);
            });
        }

        if ($status === 'active') {
            $staffQuery->where('is_active', true);
        } elseif ($status === 'inactive') {
            $staffQuery->where('is_active', false);
        }

        $page = $staffQuery
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'staff' => collect($page->items())->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'staff_id' => $member->staff_id,
                'role' => $member->roleLabel(),
                'active' => (bool) $member->is_active,
            ])->values(),
            'counts' => $counts,
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
                'has_more' => $page->hasMorePages(),
            ],
        ]);
    }

    private function administrator(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless(
            $user
                && $user->tenant_id
                && in_array($user->roleKey(), self::ROLES, true),
            403,
            'This portal is restricted to school administrators.'
        );

        return $user;
    }
}
