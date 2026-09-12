<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelRoom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileHostelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'student_search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
        $search = trim((string) ($data['search'] ?? ''));
        $studentSearch = trim((string) ($data['student_search'] ?? ''));
        $perPage = (int) ($data['per_page'] ?? 40);

        $hostels = Hostel::query()
            ->where('tenant_id', $tenantId)
            ->with(['warden:id,name,staff_id', 'rooms' => fn ($q) => $q->orderBy('room_number')])
            ->withCount(['allocations as occupied_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get()
            ->map(fn (Hostel $hostel) => $this->hostel($hostel));

        $allocations = HostelAllocation::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with(['student:id,first_name,last_name,admission_number', 'hostel:id,name', 'room:id,room_number'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->whereHas('student', function ($studentQuery) use ($search) {
                        $like = '%'.$search.'%';
                        $studentQuery->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('admission_number', 'like', $like);
                    })->orWhereHas('hostel', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('room', fn ($q) => $q->where('room_number', 'like', '%'.$search.'%'));
                });
            })
            ->latest('allocated_at')
            ->paginate($perPage);

        $allocatedStudentIds = HostelAllocation::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->pluck('student_id');
        $students = Student::query()
            ->where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
            ->whereNotIn('id', $allocatedStudentIds)
            ->when($studentSearch !== '', function ($query) use ($studentSearch) {
                $like = '%'.$studentSearch.'%';
                $query->where(function ($nested) use ($like) {
                    $nested->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('other_names', 'like', $like)
                        ->orWhere('admission_number', 'like', $like);
                });
            })
            ->with('currentClassArm.classLevel:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(100)
            ->get(['id', 'tenant_id', 'admission_number', 'first_name', 'last_name', 'other_names', 'current_class_arm_id']);

        $wardens = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_super_admin', false)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('employment_status')->orWhere('employment_status', User::STAFF_STATUS_ACTIVE))
            ->orderBy('name')
            ->get(['id', 'name', 'staff_id']);

        return response()->json([
            'capabilities' => ['manage' => $user->canManage('hostels')],
            'metrics' => [
                'hostels' => $hostels->count(),
                'rooms' => $hostels->sum(fn ($item) => count($item['rooms'])),
                'capacity' => $hostels->sum('capacity'),
                'residents' => HostelAllocation::query()->where('tenant_id', $tenantId)->where('status', 'active')->count(),
            ],
            'hostels' => $hostels,
            'allocations' => collect($allocations->items())->map(fn (HostelAllocation $allocation) => $this->allocation($allocation))->values(),
            'unallocated_students' => $students->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'class' => $student->currentClassArm?->full_name ?? 'Unassigned',
            ])->values(),
            'wardens' => $wardens->map(fn (User $warden) => [
                'id' => $warden->id,
                'name' => $warden->name,
                'staff_id' => $warden->staff_id,
            ])->values(),
            'selected' => [
                'search' => $search,
                'student_search' => $studentSearch,
            ],
            'meta' => [
                'page' => $allocations->currentPage(),
                'per_page' => $allocations->perPage(),
                'total' => $allocations->total(),
                'last_page' => $allocations->lastPage(),
                'has_more' => $allocations->hasMorePages(),
            ],
        ]);
    }

    public function storeHostel(Request $request): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'gender' => ['required', Rule::in(['male', 'female', 'mixed'])],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'warden_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($q) => $q
                ->where('tenant_id', $tenantId)
                ->where('is_super_admin', false)
                ->where('is_active', true))],
        ]);
        $hostel = Hostel::create($data + ['tenant_id' => $tenantId]);
        $hostel->load(['warden:id,name,staff_id', 'rooms'])->loadCount(['allocations as occupied_count' => fn ($q) => $q->where('status', 'active')]);

        return response()->json(['message' => 'Hostel added.', 'hostel' => $this->hostel($hostel)], 201);
    }

    public function storeRoom(Request $request, Hostel $hostel): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        abort_unless((int) $hostel->tenant_id === (int) $user->tenant_id, 404);
        $data = $request->validate([
            'room_number' => [
                'required', 'string', 'max:30',
                Rule::unique('hostel_rooms', 'room_number')->where(fn ($q) => $q->where('hostel_id', $hostel->id)),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $existingRoomCapacity = HostelRoom::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('hostel_id', $hostel->id)
            ->sum('capacity');
        abort_if($existingRoomCapacity + (int) $data['capacity'] > (int) $hostel->capacity, 422, 'Room capacities cannot exceed hostel capacity.');

        $room = HostelRoom::create($data + ['tenant_id' => $user->tenant_id, 'hostel_id' => $hostel->id]);

        return response()->json([
            'message' => 'Room added.',
            'room' => $this->room($room, 0),
        ], 201);
    }

    public function allocate(Request $request): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('status', Student::STATUS_ACTIVE))],
            'hostel_id' => ['required', Rule::exists('hostels', 'id')->where('tenant_id', $tenantId)],
            'room_id' => ['required', Rule::exists('hostel_rooms', 'id')->where('tenant_id', $tenantId)],
        ]);

        $allocation = DB::transaction(function () use ($data, $tenantId) {
            $hostel = Hostel::query()->where('tenant_id', $tenantId)->whereKey($data['hostel_id'])->lockForUpdate()->firstOrFail();
            $room = HostelRoom::query()->where('tenant_id', $tenantId)->whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            abort_unless((int) $room->hostel_id === (int) $hostel->id, 422, 'The selected room does not belong to this hostel.');

            $alreadyAllocated = HostelAllocation::query()
                ->where('tenant_id', $tenantId)
                ->where('student_id', $data['student_id'])
                ->where('status', 'active')
                ->exists();
            abort_if($alreadyAllocated, 409, 'This student already has an active hostel allocation.');

            $roomOccupied = HostelAllocation::query()
                ->where('tenant_id', $tenantId)
                ->where('room_id', $room->id)
                ->where('status', 'active')
                ->count();
            abort_if($roomOccupied >= (int) $room->capacity, 409, 'This room is already at full capacity.');

            $hostelOccupied = HostelAllocation::query()
                ->where('tenant_id', $tenantId)
                ->where('hostel_id', $hostel->id)
                ->where('status', 'active')
                ->count();
            abort_if($hostelOccupied >= (int) $hostel->capacity, 409, 'This hostel is already at full capacity.');

            $sessionId = AcademicSession::query()
                ->where('tenant_id', $tenantId)
                ->where('is_current', true)
                ->value('id');

            return HostelAllocation::create([
                'tenant_id' => $tenantId,
                'student_id' => $data['student_id'],
                'hostel_id' => $hostel->id,
                'room_id' => $room->id,
                'session_id' => $sessionId,
                'allocated_at' => now()->toDateString(),
                'status' => 'active',
            ]);
        });
        $allocation->load(['student:id,first_name,last_name,admission_number', 'hostel:id,name', 'room:id,room_number']);

        return response()->json([
            'message' => 'Student allocated to hostel room.',
            'allocation' => $this->allocation($allocation),
        ], 201);
    }

    public function vacate(Request $request, HostelAllocation $allocation): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        abort_unless((int) $allocation->tenant_id === (int) $user->tenant_id, 404);
        abort_if($allocation->status !== 'active', 409, 'This allocation is already closed.');
        $allocation->update(['status' => 'vacated', 'vacated_at' => now()->toDateString()]);

        return response()->json(['message' => 'Hostel allocation ended.']);
    }

    private function hostel(Hostel $hostel): array
    {
        return [
            'id' => $hostel->id,
            'name' => $hostel->name,
            'gender' => $hostel->gender,
            'capacity' => (int) $hostel->capacity,
            'occupied' => (int) ($hostel->occupied_count ?? 0),
            'warden_id' => $hostel->warden_id,
            'warden' => $hostel->warden?->name,
            'rooms' => $hostel->rooms->map(function (HostelRoom $room) {
                $occupied = HostelAllocation::query()
                    ->where('tenant_id', $room->tenant_id)
                    ->where('room_id', $room->id)
                    ->where('status', 'active')
                    ->count();

                return $this->room($room, $occupied);
            })->values(),
        ];
    }

    private function room(HostelRoom $room, int $occupied): array
    {
        return [
            'id' => $room->id,
            'hostel_id' => $room->hostel_id,
            'room_number' => $room->room_number,
            'capacity' => (int) $room->capacity,
            'occupied' => $occupied,
            'full' => $occupied >= (int) $room->capacity,
        ];
    }

    private function allocation(HostelAllocation $allocation): array
    {
        return [
            'id' => $allocation->id,
            'student_id' => $allocation->student_id,
            'student' => $allocation->student?->full_name ?? 'Student',
            'admission_number' => $allocation->student?->admission_number,
            'hostel_id' => $allocation->hostel_id,
            'hostel' => $allocation->hostel?->name,
            'room_id' => $allocation->room_id,
            'room' => $allocation->room?->room_number,
            'allocated_at' => $allocation->allocated_at?->toDateString(),
            'status' => $allocation->status,
        ];
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School hostel access required.');
        abort_unless($user->tenant_id, 403, 'School hostel access required.');
        abort_unless(
            $manage ? $user->canManage('hostels') : $user->canAccessModule('hostels'),
            403,
            $manage ? 'Hostel management permission required.' : 'Hostel access required.'
        );

        return $user;
    }
}
