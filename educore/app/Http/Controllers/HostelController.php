<?php

namespace App\Http\Controllers;

use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelRoom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HostelController extends Controller
{
    private function guard(bool $manage = false): void
    {
        $user = auth()->user();
        abort_unless(
            $user && ($manage ? $user->canManage('hostels') : $user->canAccessModule('hostels')),
            403
        );
    }

    public function index()
    {
        $this->guard();
        $hostels = Hostel::with(['rooms', 'warden'])->orderBy('name')->get();
        $allocations = HostelAllocation::with(['student', 'hostel', 'room'])
            ->where('status', 'active')
            ->latest()
            ->paginate(25);

        $students = Student::where('status', Student::STATUS_ACTIVE)->orderBy('first_name')->get();
        $wardens = User::activeStaff(auth()->user()->tenant_id)->orderBy('name')->get();

        return view('hostels.index', compact('hostels', 'allocations', 'students', 'wardens'));
    }

    public function storeHostel(Request $request)
    {
        $this->guard(true);
        $tenantId = (int) auth()->user()->tenant_id;
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'gender'    => ['required', 'in:male,female,mixed'],
            'capacity'  => ['required', 'integer', 'min:1'],
            'warden_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
        ]);

        Hostel::create($data);

        return back()->with('success', 'Hostel added.');
    }

    public function storeRoom(Request $request, Hostel $hostel)
    {
        $this->guard(true);
        $data = $request->validate([
            'room_number' => ['required', 'string', 'max:30'],
            'capacity'    => ['required', 'integer', 'min:1'],
        ]);
        $data['hostel_id'] = $hostel->id;

        HostelRoom::create($data);

        return back()->with('success', 'Room added.');
    }

    public function allocate(Request $request)
    {
        $this->guard(true);
        $tenantId = (int) auth()->user()->tenant_id;
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where('tenant_id', $tenantId)],
            'hostel_id'  => ['required', Rule::exists('hostels', 'id')->where('tenant_id', $tenantId)],
            'room_id'    => ['required', Rule::exists('hostel_rooms', 'id')->where('tenant_id', $tenantId)],
        ]);

        $room = HostelRoom::findOrFail($data['room_id']);
        if ((int) $room->hostel_id !== (int) $data['hostel_id']) {
            return back()->withErrors(['room_id' => 'The selected room does not belong to this hostel.']);
        }
        if (!$room->hasSpace()) {
            return back()->withErrors(['room_id' => 'This room is already at full capacity.']);
        }

        HostelAllocation::create([
            'student_id'          => $data['student_id'],
            'hostel_id'           => $data['hostel_id'],
            'room_id'             => $data['room_id'],
            'allocated_at'        => now(),
            'status'              => 'active',
        ]);

        return back()->with('success', 'Student allocated to hostel room.');
    }

    public function vacate(HostelAllocation $allocation)
    {
        $this->guard(true);
        $allocation->update(['status' => 'vacated', 'vacated_at' => now()]);
        return back()->with('success', 'Allocation ended.');
    }

    public function roomsFor(Hostel $hostel)
    {
        $this->guard();
        return response()->json(
            $hostel->rooms()->get()->map(fn ($r) => [
                'id' => $r->id,
                'label' => $r->room_number . ' (' . $r->occupiedCount() . '/' . $r->capacity . ')',
                'full' => !$r->hasSpace(),
            ])
        );
    }
}
