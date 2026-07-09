<?php

namespace App\Http\Controllers;

use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelRoom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class HostelController extends Controller
{
    public function index()
    {
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
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'gender'    => ['required', 'in:male,female,mixed'],
            'capacity'  => ['required', 'integer', 'min:1'],
            'warden_id' => ['nullable', 'exists:users,id'],
        ]);

        Hostel::create($data);

        return back()->with('success', 'Hostel added.');
    }

    public function storeRoom(Request $request, Hostel $hostel)
    {
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
        $data = $request->validate([
            'student_id'          => ['required', 'exists:students,id'],
            'hostel_id'           => ['required', 'exists:hostels,id'],
            'room_id'             => ['required', 'exists:hostel_rooms,id'],
            'boarding_fee_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $room = HostelRoom::findOrFail($data['room_id']);
        if (!$room->hasSpace()) {
            return back()->withErrors(['room_id' => 'This room is already at full capacity.']);
        }

        HostelAllocation::create([
            'student_id'          => $data['student_id'],
            'hostel_id'           => $data['hostel_id'],
            'room_id'             => $data['room_id'],
            'boarding_fee_amount' => $data['boarding_fee_amount'] ?? 0,
            'allocated_at'        => now(),
            'status'              => 'active',
        ]);

        return back()->with('success', 'Student allocated to hostel room.');
    }

    public function vacate(HostelAllocation $allocation)
    {
        $allocation->update(['status' => 'vacated', 'vacated_at' => now()]);
        return back()->with('success', 'Allocation ended.');
    }

    public function markFeePaid(HostelAllocation $allocation)
    {
        $allocation->update(['boarding_fee_status' => 'paid']);
        return back()->with('success', 'Boarding fee marked as paid.');
    }

    public function roomsFor(Hostel $hostel)
    {
        return response()->json(
            $hostel->rooms()->get()->map(fn ($r) => [
                'id' => $r->id,
                'label' => $r->room_number . ' (' . $r->occupiedCount() . '/' . $r->capacity . ')',
                'full' => !$r->hasSpace(),
            ])
        );
    }
}
