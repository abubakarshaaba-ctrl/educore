<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\MessageThread;
use App\Models\MessageThreadReply;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\StaffAttendanceRecord;
use App\Models\Term;
use App\Models\TimetablePeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class StaffPortalController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────
    public function dashboard()
    {
        $user   = Auth::user();
        $tenant = $user->tenant;

        $currentTerm = Term::where('is_current', true)->first();

        // Last 3 payroll items for this staff member
        $recentPayroll = PayrollItem::where('staff_id', $user->id)
            ->with('period')
            ->latest()
            ->limit(3)
            ->get();

        // This term's timetable if user is a teacher
        $timetable = collect();
        if (Schema::hasTable('timetable_periods')) {
            $timetable = TimetablePeriod::where('teacher_id', $user->id)
                ->with(['subject', 'classArm.classLevel'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->groupBy(fn($p) => ucfirst($p->day_of_week));
        }

        // Recent attendance for this staff
        $attendance = collect();
        if (Schema::hasTable('staff_attendance_records')) {
            $attendance = StaffAttendanceRecord::where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get();
        }

        // School announcements targeted at staff/all
        $announcements = Announcement::where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->whereIn('audience', ['all', 'staff'])
            ->latest(Schema::hasColumn('announcements', 'publish_date') ? 'publish_date' : 'created_at')
            ->limit(5)
            ->get();

        $latestPayslip = $recentPayroll->first();

        return view('portal.staff.dashboard', compact(
            'user', 'tenant', 'currentTerm',
            'recentPayroll', 'timetable', 'attendance',
            'announcements', 'latestPayslip'
        ));
    }

    // ── Payroll History ───────────────────────────────────────────────
    public function payroll(Request $request)
    {
        $user = Auth::user();

        $items = PayrollItem::where('staff_id', $user->id)
            ->with('period')
            ->latest()
            ->paginate(20);

        $totals = PayrollItem::where('staff_id', $user->id)
            ->selectRaw('SUM(gross_pay) as total_gross, SUM(total_deductions) as total_deductions, SUM(net_pay) as total_net, COUNT(*) as count')
            ->first();

        return view('portal.staff.payroll', compact('user', 'items', 'totals'));
    }

    // ── Payslip Print / PDF ───────────────────────────────────────────
    public function payslipPrint(PayrollPeriod $period)
    {
        $user = Auth::user();

        $item = PayrollItem::where('staff_id', $user->id)
            ->where('payroll_period_id', $period->id)
            ->firstOrFail();

        abort_unless($item->tenant_id === $user->tenant_id, 403);

        $tenant = $user->tenant;

        return view('payroll.payslip-pdf', compact('period', 'item', 'tenant'));
    }

    // ── Messages (student-linked threads this staff member is party to) ──
    public function messages()
    {
        $userId = Auth::id();

        $threads = MessageThread::where(function ($q) use ($userId) {
                $q->where('initiated_by', $userId)
                  ->orWhereHas('replies', fn ($r) => $r->where('sender_id', $userId));
            })
            ->with(['student', 'initiator', 'replies' => fn ($q) => $q->latest()->limit(1)])
            ->latest()
            ->paginate(20);

        $unreadCount = MessageThread::whereHas('replies', fn ($q) =>
            $q->where('is_read', false)->where('sender_id', '!=', $userId)
        )->count();

        return view('portal.staff.messages', compact('threads', 'unreadCount', 'userId'));
    }

    public function messageThread(MessageThread $thread)
    {
        $user = Auth::user();
        abort_unless(
            (int) $thread->initiated_by === (int) $user->id
                || $thread->replies()->where('sender_id', $user->id)->exists(),
            403,
            'You are not a participant in this conversation.'
        );

        $thread->load(['student', 'initiator', 'replies.sender']);

        $thread->replies()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('portal.staff.message-thread', compact('thread'));
    }

    public function messageReply(Request $request, MessageThread $thread)
    {
        $user = Auth::user();
        abort_unless(
            (int) $thread->initiated_by === (int) $user->id
                || $thread->replies()->where('sender_id', $user->id)->exists(),
            403,
            'You are not a participant in this conversation.'
        );

        $data = $request->validate(['body' => ['required', 'string']]);

        MessageThreadReply::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'body'      => $data['body'],
        ]);

        return back()->with('success', 'Reply sent.');
    }
}
