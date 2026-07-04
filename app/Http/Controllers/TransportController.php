<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransportController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function tenantUserRule(array $roles)
    {
        return Rule::exists('users', 'id')->where(fn ($query) => $query
            ->where('tenant_id', $this->tenantId())
            ->where('is_super_admin', false)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('employment_status')->orWhere('employment_status', \App\Models\User::STAFF_STATUS_ACTIVE))
            ->whereIn('role', $roles));
    }

    // ── Routes ────────────────────────────────────────────────────────
    public function routes()
    {
        $routes     = \App\Models\TransportRoute::with(['driver','assistant','bus'])->get();
        $drivers    = \App\Models\User::activeStaff($this->tenantId())
            ->whereIn('role', ['driver','transport_officer'])
            ->orderBy('name')->get();
        $assistants = \App\Models\User::activeStaff($this->tenantId())
            ->whereIn('role', ['bus_assistant','driver','transport_officer'])
            ->orderBy('name')->get();
        $buses      = \App\Models\TransportBus::all();
        return view('transport.routes', compact('routes', 'drivers', 'assistants', 'buses'));
    }

    public function storeRoute(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string'],
            'fare'         => ['required', 'numeric', 'min:0'],
            'morning_time' => ['nullable', 'string', 'max:10'],
            'evening_time' => ['nullable', 'string', 'max:10'],
            'bus_id'       => ['nullable', Rule::exists('transport_buses', 'id')->where('tenant_id', $this->tenantId())],
            'driver_id'    => ['nullable', $this->tenantUserRule(['driver','transport_officer'])],
            'assistant_id' => ['nullable', $this->tenantUserRule(['bus_assistant','driver','transport_officer'])],
        ]);
        \App\Models\TransportRoute::create($data);
        return back()->with('success', 'Route created.');
    }

    public function updateRoute(Request $request, \App\Models\TransportRoute $route)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'fare'         => ['required', 'numeric', 'min:0'],
            'morning_time' => ['nullable', 'string', 'max:10'],
            'evening_time' => ['nullable', 'string', 'max:10'],
            'bus_id'       => ['nullable', Rule::exists('transport_buses', 'id')->where('tenant_id', $this->tenantId())],
            'driver_id'    => ['nullable', $this->tenantUserRule(['driver','transport_officer'])],
            'assistant_id' => ['nullable', $this->tenantUserRule(['bus_assistant','driver','transport_officer'])],
            'is_active'    => ['boolean'],
        ]);
        $route->update($data);
        return back()->with('success', 'Route updated.');
    }

    public function destroyRoute(\App\Models\TransportRoute $route)
    {
        $route->delete();
        return back()->with('success', 'Route removed.');
    }

    // ── Buses ─────────────────────────────────────────────────────────
    public function buses()
    {
        $buses = \App\Models\TransportBus::all();
        return view('transport.buses', compact('buses'));
    }

    public function storeBus(Request $request)
    {
        $data = $request->validate([
            'plate_number'  => ['required', 'string', 'max:20'],
            'model'         => ['nullable', 'string', 'max:80'],
            'capacity'      => ['required', 'integer', 'min:1'],
            'year'          => ['nullable', 'integer', 'min:1990'],
        ]);
        \App\Models\TransportBus::create($data);
        return back()->with('success', 'Bus added.');
    }

    public function destroyBus(\App\Models\TransportBus $bus)
    {
        $bus->delete();
        return back()->with('success', 'Bus removed.');
    }

    // ── Student Assignments ───────────────────────────────────────────
    public function assignments()
    {
        $routes   = \App\Models\TransportRoute::where('is_active', true)->get();
        $students = \App\Models\Student::with(['currentClassArm.classLevel', 'transportAssignment.route'])
                        ->where('status', \App\Models\Student::STATUS_ACTIVE)->orderBy('first_name')->get();
        return view('transport.assignments', compact('routes', 'students'));
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId())->where('status', \App\Models\Student::STATUS_ACTIVE))],
            'route_id'   => ['required', Rule::exists('transport_routes', 'id')->where('tenant_id', $this->tenantId())],
            'pickup_stop'=> ['nullable', 'string', 'max:120'],
            'direction'  => ['required', 'in:both,morning,evening'],
        ]);

        \App\Models\TransportAssignment::updateOrCreate(
            ['student_id' => $data['student_id']],
            $data
        );
        return back()->with('success', 'Student assigned to route.');
    }

    public function unassign(\App\Models\Student $student)
    {
        \App\Models\TransportAssignment::where('student_id', $student->id)->delete();
        return back()->with('success', 'Assignment removed.');
    }

    // ── Manifest (students on a route) ────────────────────────────────
    public function manifest(\App\Models\TransportRoute $route)
    {
        $assignments = \App\Models\TransportAssignment::where('route_id', $route->id)
            ->with(['student.currentClassArm.classLevel', 'student.guardians'])
            ->get();
        return view('transport.manifest', compact('route', 'assignments'));
    }
}
