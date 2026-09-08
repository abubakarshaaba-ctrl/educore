<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TransportAssignment;
use App\Models\TransportBus;
use App\Models\TransportRoute;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransportOfficerController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
        $search = trim((string) ($data['search'] ?? ''));
        $perPage = (int) ($data['per_page'] ?? 40);

        $routes = TransportRoute::where('tenant_id', $tenantId)
            ->with(['bus', 'driver:id,name', 'assistant:id,name'])
            ->withCount('assignments')->orderBy('name')->get()->map(fn (TransportRoute $route) => [
                'id' => $route->id,
                'name' => $route->name,
                'description' => $route->description,
                'fare' => (float) $route->fare,
                'morning_time' => $route->morning_time,
                'evening_time' => $route->evening_time,
                'active' => (bool) $route->is_active,
                'bus' => $route->bus?->plate_number ?? 'Not assigned',
                'driver' => $route->driver?->name ?? 'Not assigned',
                'assistant' => $route->assistant?->name ?? 'Not assigned',
                'riders' => $route->assignments_count,
                'capacity' => $route->bus?->capacity,
            ]);
        $buses = TransportBus::where('tenant_id', $tenantId)->orderBy('plate_number')->get()->map(fn (TransportBus $bus) => [
            'id' => $bus->id,
            'plate_number' => $bus->plate_number,
            'model' => $bus->model,
            'capacity' => $bus->capacity,
            'year' => $bus->year,
            'active' => (bool) $bus->is_active,
        ]);
        $assignedIds = TransportAssignment::where('tenant_id', $tenantId)->pluck('student_id');
        $unassignedBase = Student::query()
            ->where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
            ->whereNotIn('id', $assignedIds);
        $unassignedCount = (clone $unassignedBase)->count();
        $unassigned = (clone $unassignedBase)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
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
            ->paginate($perPage);

        return response()->json([
            'capabilities' => [
                'manage' => $user->canManage('transport'),
            ],
            'metrics' => [
                'routes' => $routes->count(),
                'active_buses' => $buses->where('active', true)->count(),
                'assigned_students' => $assignedIds->count(),
                'unassigned_students' => $unassignedCount,
            ],
            'routes' => $routes,
            'buses' => $buses,
            'unassigned_students' => collect($unassigned->items())->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => trim("{$student->first_name} {$student->last_name}"),
                'admission_number' => $student->admission_number,
                'class' => $student->currentClassArm?->full_name ?? 'Unassigned',
            ])->values(),
            'selected' => [
                'search' => $search,
            ],
            'meta' => [
                'page' => $unassigned->currentPage(),
                'per_page' => $unassigned->perPage(),
                'total' => $unassigned->total(),
                'last_page' => $unassigned->lastPage(),
                'has_more' => $unassigned->hasMorePages(),
            ],
        ]);
    }

    public function manifest(Request $request, TransportRoute $route)
    {
        $user = $this->guard($request);
        abort_unless((int) $route->tenant_id === (int) $user->tenant_id, 404);
        $items = TransportAssignment::where('tenant_id', $user->tenant_id)->where('route_id', $route->id)
            ->with('student.currentClassArm.classLevel:id,name')->get()->map(fn (TransportAssignment $assignment) => [
                'assignment_id' => $assignment->id,
                'student_id' => $assignment->student_id,
                'name' => trim(($assignment->student?->first_name ?? '').' '.($assignment->student?->last_name ?? '')),
                'admission_number' => $assignment->student?->admission_number,
                'class' => $assignment->student?->currentClassArm?->full_name ?? 'Unassigned',
                'pickup_stop' => $assignment->pickup_stop,
                'direction' => $assignment->direction,
            ]);

        return response()->json([
            'capabilities' => [
                'manage' => $user->canManage('transport'),
            ],
            'route' => ['id' => $route->id, 'name' => $route->name],
            'manifest' => $items,
        ]);
    }

    public function assign(Request $request)
    {
        $user = $this->guard($request, manage: true);
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where(fn ($q) => $q->where('tenant_id', $user->tenant_id)->where('status', Student::STATUS_ACTIVE))],
            'route_id' => ['required', Rule::exists('transport_routes', 'id')->where(fn ($q) => $q->where('tenant_id', $user->tenant_id)->where('is_active', true))],
            'pickup_stop' => ['nullable', 'string', 'max:150'],
            'direction' => ['required', 'in:both,morning,evening'],
        ]);
        TransportAssignment::updateOrCreate(
            ['tenant_id' => $user->tenant_id, 'student_id' => $data['student_id']],
            $data + ['tenant_id' => $user->tenant_id]
        );

        return response()->json(['message' => 'Student transport assignment saved.']);
    }

    public function unassign(Request $request, Student $student)
    {
        $user = $this->guard($request, manage: true);
        abort_unless((int) $student->tenant_id === (int) $user->tenant_id, 404);

        $deleted = TransportAssignment::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('student_id', $student->id)
            ->delete();

        abort_if($deleted === 0, 404, 'Transport assignment not found.');

        return response()->json(['message' => 'Student transport assignment removed.']);
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School transport access required.');
        abort_unless($user->tenant_id, 403, 'School transport access required.');
        abort_unless(
            $manage ? $user->canManage('transport') : $user->canAccessModule('transport'),
            403,
            $manage ? 'Transport management permission required.' : 'Transport access required.'
        );

        return $user;
    }
}
