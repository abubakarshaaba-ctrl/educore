<?php

namespace App\Http\Controllers;

use App\Models\PayrollItem;
use App\Models\StaffAttendanceRecord;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffArchiveController extends Controller
{
    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizePermission('staff.archive.view');

        $query = $this->archiveQuery()
            ->with('currentWorkHistory');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('staff_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereIn('role', User::roleAliasesFor($request->role));
        }

        if ($request->filled('status')) {
            $query->where('employment_status', $request->status);
        }

        if ($request->filled('exit_from')) {
            $query->whereDate('employment_ended_at', '>=', $request->date('exit_from'));
        }

        if ($request->filled('exit_to')) {
            $query->whereDate('employment_ended_at', '<=', $request->date('exit_to'));
        }

        $summary = User::archivedStaff($this->tenantId())
            ->selectRaw('employment_status, COUNT(*) as count')
            ->groupBy('employment_status')
            ->pluck('count', 'employment_status');

        $staff = $query->orderByDesc('employment_ended_at')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('staff.archive.index', [
            'staff' => $staff,
            'summary' => $summary,
            'statusLabels' => User::STAFF_STATUS_LABELS,
        ]);
    }

    public function show(User $staff): View
    {
        $this->authorizePermission('staff.archive.view');

        $staff = $this->archiveQuery()
            ->whereKey($staff->id)
            ->with([
                'staffStatusHistories.changedBy',
                'staffStatusHistories.approvedBy',
                'workHistories.recordedBy',
                'workHistories.approvedBy',
            ])
            ->firstOrFail();

        $attendanceCount = StaffAttendanceRecord::where('tenant_id', $this->tenantId())
            ->where('user_id', $staff->id)
            ->count();

        $payrollSummary = PayrollItem::where('tenant_id', $this->tenantId())
            ->where('staff_id', $staff->id)
            ->selectRaw('COUNT(*) as payslip_count, COALESCE(SUM(net_pay),0) as net_total')
            ->first();

        return view('staff.archive.show', compact('staff', 'attendanceCount', 'payrollSummary'));
    }

    public function export(Request $request)
    {
        $this->authorizePermission('staff.archive.export');

        $rows = $this->archiveQuery()
            ->orderBy('name')
            ->get();

        $this->auditLogger->record(
            $this->tenantId(),
            auth()->user(),
            auth()->user(),
            'staff.archive.exported',
            [],
            ['count' => $rows->count()],
            'Staff archive export',
            $request
        );

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Staff ID', 'Name', 'Email', 'Role', 'Employment Status', 'Started', 'Ended', 'Exit Reason']);

            foreach ($rows as $staff) {
                fputcsv($out, [
                    $staff->staff_id,
                    $staff->name,
                    $staff->email,
                    $staff->roleLabel(),
                    $staff->employmentStatusLabel(),
                    optional($staff->employment_started_at)->toDateString(),
                    optional($staff->employment_ended_at)->toDateString(),
                    $staff->exit_reason,
                ]);
            }

            fclose($out);
        }, 'staff_archive_' . now()->format('Ymd_His') . '.csv');
    }

    private function archiveQuery()
    {
        return User::archivedStaff($this->tenantId());
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        abort_unless($tenantId, 403, 'A tenant context is required.');

        return (int) $tenantId;
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
