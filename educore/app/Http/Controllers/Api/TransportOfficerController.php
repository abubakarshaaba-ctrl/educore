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
        $tenantId = $user->tenant_id;
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
        $unassigned = Student::where('tenant_id', $tenantId)->where('status', Student::STATUS_ACTIVE)
            ->whereNotIn('id', $assignedIds)->with('currentClassArm.classLevel:id,name')->orderBy('first_name')->limit(100)->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => trim("{$student->first_name} {$student->last_name}"),
                'admission_number' => $student->admission_number,
                'class' => $student->currentClassArm?->full_name ?? 'Unassigned',
            ]);

        return response()->json([
            'metrics' => [
                'routes' => $routes->count(),
                'active_buses' => $buses->where('active', true)->count(),
                'assigned_students' => $assignedIds->count(),
                'unassigned_students' => $unassigned->count(),
            ],
            'routes' => $routes,
            'buses' => $buses,
            'unassigned_students' => $unassigned,
        ]);
    }

    public function manifest(Request $request, TransportRoute $route)
    {
        $user = $this->guard($request);
        abort_unless((int) $route->tenant_id === (int) $user->tenant_id, 404);
        $items = TransportAssignment::where('tenant_id', $user->tenant_id)->where('route_id', $route->id)
            ->with('student.currentClassArm.classLevel:id,name')->get()->map(fn (TransportAssignment $assignment) => [
                'student_id' => $assignment->student_id,
                'name' => trim(($assignment->student?->first_name ?? '').' '.($assignment->student?->last_name ?? '')),
                'admission_number' => $assignment->student?->admission_number,
                'class' => $assignment->student?->currentClassArm?->full_name ?? 'Unassigned',
                'pickup_stop' => $assignment->pickup_stop,
                'direction' => $assignment->direction,
            ]);
        return response()->json(['route' => ['id' => $route->id, 'name' => $route->name], 'manifest' => $items]);
    }

    public function assign(Request $request)
    {
        $user = $this->guard($request);
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

    private function guard(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user && in_array($user->roleKey(), ['transport_officer', 'admin', 'principal', 'head', 'head_teacher'], true), 403, 'Transport Officer access required.');
        return $user;
    }
}
