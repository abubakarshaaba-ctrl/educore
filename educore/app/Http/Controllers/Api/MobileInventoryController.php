<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolAsset;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileInventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['all', 'in_use', 'in_storage', 'under_repair', 'disposed'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
        $search = trim((string) ($data['search'] ?? ''));
        $status = (string) ($data['status'] ?? 'all');
        $perPage = (int) ($data['per_page'] ?? 40);
        $tenantId = (int) $user->tenant_id;

        $query = SchoolAsset::query()
            ->where('tenant_id', $tenantId)
            ->with('assignedTo:id,name,staff_id')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($nested) use ($like) {
                    $nested->where('name', 'like', $like)
                        ->orWhere('category', 'like', $like)
                        ->orWhere('serial_number', 'like', $like)
                        ->orWhere('location', 'like', $like);
                });
            })
            ->orderBy('name');

        $assets = $query->paginate($perPage);
        $all = SchoolAsset::query()->where('tenant_id', $tenantId);
        $staff = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_super_admin', false)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('employment_status')->orWhere('employment_status', User::STAFF_STATUS_ACTIVE))
            ->orderBy('name')
            ->get(['id', 'name', 'staff_id']);

        return response()->json([
            'capabilities' => ['manage' => $user->canManage('inventory')],
            'metrics' => [
                'total' => (clone $all)->count(),
                'in_use' => (clone $all)->where('status', 'in_use')->count(),
                'under_repair' => (clone $all)->where('status', 'under_repair')->count(),
                'damaged' => (clone $all)->where('condition', 'damaged')->count(),
            ],
            'assets' => collect($assets->items())->map(fn (SchoolAsset $asset) => $this->asset($asset))->values(),
            'staff' => $staff->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'staff_id' => $member->staff_id,
            ])->values(),
            'status_options' => [
                ['key' => 'in_use', 'label' => 'In use'],
                ['key' => 'in_storage', 'label' => 'In storage'],
                ['key' => 'under_repair', 'label' => 'Under repair'],
                ['key' => 'disposed', 'label' => 'Disposed'],
            ],
            'condition_options' => [
                ['key' => 'new', 'label' => 'New'],
                ['key' => 'good', 'label' => 'Good'],
                ['key' => 'fair', 'label' => 'Fair'],
                ['key' => 'poor', 'label' => 'Poor'],
                ['key' => 'damaged', 'label' => 'Damaged'],
            ],
            'selected' => ['search' => $search, 'status' => $status],
            'meta' => [
                'page' => $assets->currentPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
                'last_page' => $assets->lastPage(),
                'has_more' => $assets->hasMorePages(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        $data = $request->validate($this->rules((int) $user->tenant_id, create: true));
        $asset = SchoolAsset::create($data + ['tenant_id' => $user->tenant_id]);
        $asset->load('assignedTo:id,name,staff_id');

        return response()->json([
            'message' => 'Asset added to inventory.',
            'asset' => $this->asset($asset),
        ], 201);
    }

    public function update(Request $request, SchoolAsset $asset): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        abort_unless((int) $asset->tenant_id === (int) $user->tenant_id, 404);
        $data = $request->validate($this->rules((int) $user->tenant_id, create: false));
        $asset->update($data);
        $asset->refresh()->load('assignedTo:id,name,staff_id');

        return response()->json([
            'message' => 'Asset updated.',
            'asset' => $this->asset($asset),
        ]);
    }

    public function destroy(Request $request, SchoolAsset $asset): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        abort_unless((int) $asset->tenant_id === (int) $user->tenant_id, 404);
        $asset->delete();

        return response()->json(['message' => 'Asset removed from inventory.']);
    }

    private function rules(int $tenantId, bool $create): array
    {
        $staffRule = Rule::exists('users', 'id')->where(fn ($q) => $q
            ->where('tenant_id', $tenantId)
            ->where('is_super_admin', false)
            ->where('is_active', true));

        $rules = [
            'name' => [$create ? 'required' : 'sometimes', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:150'],
            'assigned_to' => ['nullable', $staffRule],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'condition' => [$create ? 'required' : 'sometimes', Rule::in(['new', 'good', 'fair', 'poor', 'damaged'])],
            'status' => [$create ? 'required' : 'sometimes', Rule::in(['in_use', 'in_storage', 'under_repair', 'disposed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if (! $create) {
            foreach (['category', 'serial_number', 'location', 'assigned_to', 'purchase_date', 'purchase_cost', 'notes'] as $field) {
                array_unshift($rules[$field], 'sometimes');
            }
        }

        return $rules;
    }

    private function asset(SchoolAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'name' => $asset->name,
            'category' => $asset->category,
            'serial_number' => $asset->serial_number,
            'location' => $asset->location,
            'assigned_to' => $asset->assigned_to,
            'assigned_to_name' => $asset->assignedTo?->name,
            'purchase_date' => $asset->purchase_date?->toDateString(),
            'purchase_cost' => $asset->purchase_cost !== null ? (float) $asset->purchase_cost : null,
            'condition' => $asset->condition,
            'status' => $asset->status,
            'notes' => $asset->notes,
        ];
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School inventory access required.');
        abort_unless($user->tenant_id, 403, 'School inventory access required.');
        abort_unless(
            $manage ? $user->canManage('inventory') : $user->canAccessModule('inventory'),
            403,
            $manage ? 'Inventory management permission required.' : 'Inventory access required.'
        );

        return $user;
    }
}
