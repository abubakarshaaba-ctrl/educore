<?php

namespace App\Http\Controllers;

use App\Models\StaffLeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;

class StaffLeaveController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = auth()->user()->canAccessExactModule('students') || auth()->user()->isSuperAdmin();

        $requests = StaffLeaveRequest::with(['user', 'reviewer'])
            ->when(!$isAdmin, fn ($q) => $q->where('user_id', auth()->id()))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('leave.index', compact('requests', 'isAdmin'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'leave_type' => ['required', 'in:annual,sick,maternity,paternity,compassionate,unpaid,other'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['nullable', 'string'],
        ]);

        $data['user_id'] = auth()->id();
        $data['days_requested'] = \Carbon\Carbon::parse($data['start_date'])->diffInDays(\Carbon\Carbon::parse($data['end_date'])) + 1;
        $data['status'] = 'pending';

        StaffLeaveRequest::create($data);

        return back()->with('success', 'Leave request submitted.');
    }

    public function approve(Request $request, StaffLeaveRequest $leaveRequest)
    {
        $leaveRequest->update([
            'status'       => 'approved',
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
            'review_notes' => $request->input('review_notes'),
        ]);

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, StaffLeaveRequest $leaveRequest)
    {
        $leaveRequest->update([
            'status'       => 'rejected',
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
            'review_notes' => $request->input('review_notes'),
        ]);

        return back()->with('success', 'Leave request rejected.');
    }

    public function cancel(StaffLeaveRequest $leaveRequest)
    {
        abort_unless($leaveRequest->user_id === auth()->id(), 403);
        $leaveRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Leave request cancelled.');
    }
}
