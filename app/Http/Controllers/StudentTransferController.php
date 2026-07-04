<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\Tenant;
use App\Services\LifecycleAuditLogger;
use Illuminate\Http\Request;

class StudentTransferController extends Controller
{
    public function __construct(private LifecycleAuditLogger $auditLogger)
    {
    }

    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $outgoing = StudentTransfer::where('from_tenant_id', $tenantId)
                        ->latest()->get();

        $incoming = StudentTransfer::where('to_tenant_id', $tenantId)
                        ->latest()->get();

        // FIX: tenants table uses status (string enum), NOT is_active (boolean)
        $tenants = Tenant::where('id', '!=', $tenantId)
                        ->where('status', 'active')   // ← was: where('is_active', true)
                        ->get();

        $activeStudents = Student::where('status', 'active')
                            ->orderBy('last_name')
                            ->get();

        return view('students.transfers',
            compact('outgoing', 'incoming', 'tenants', 'activeStudents'));
    }

    public function request(Request $request)
    {
        $data = $request->validate([
            'student_id'   => ['required', 'exists:students,id'],
            'to_tenant_id' => ['required', 'exists:tenants,id'],
            'reason'       => ['nullable', 'string', 'max:500'],
        ]);

        $student = Student::findOrFail($data['student_id']);

        // Ensure student belongs to THIS school
        abort_if($student->tenant_id !== auth()->user()->tenant_id, 403,
            'You can only transfer students from your own school.');

        // Prevent duplicate pending request
        $existing = StudentTransfer::where('student_id', $student->id)
                        ->where('status', 'pending')
                        ->exists();

        if ($existing) {
            return back()->withErrors([
                'student_id' => 'This student already has a pending transfer request.'
            ]);
        }

        $transfer = StudentTransfer::create([
            'from_tenant_id'   => auth()->user()->tenant_id,
            'to_tenant_id'     => $data['to_tenant_id'],
            'student_id'       => $student->id,
            'student_name'     => $student->full_name,
            'admission_number' => $student->admission_number ?? null,
            'reason'           => $data['reason'],
            'requested_by'     => auth()->id(),
            'status'           => 'pending',
        ]);

        $this->auditLogger->record(
            auth()->user()->tenant_id,
            auth()->user(),
            $transfer,
            'student.transfer.requested',
            [],
            ['to_tenant_id' => $data['to_tenant_id'], 'student_id' => $student->id],
            $data['reason'] ?? null,
            $request
        );

        return back()->with('success',
            'Transfer request submitted. Awaiting approval from the receiving school.');
    }

    public function approve(StudentTransfer $transfer)
    {
        // Only the RECEIVING school can approve
        abort_unless($transfer->to_tenant_id === auth()->user()->tenant_id, 403,
            'Only the receiving school can approve this transfer.');

        abort_if($transfer->status !== 'pending', 422,
            'This transfer has already been processed.');

        // Move student to receiving school
        $student = Student::withoutTenantScope()->find($transfer->student_id);

        if ($student) {
            $student->update([
                'tenant_id'           => $transfer->to_tenant_id,
                'current_class_arm_id'=> null,   // reset class assignment
                'status'              => 'active',
            ]);

            $transfer->update([
                'status'      => 'completed',
                'approved_at' => now(),
            ]);
        } else {
            $transfer->update([
                'status'      => 'approved',
                'approved_at' => now(),
            ]);
        }

        $this->auditLogger->record(
            auth()->user()->tenant_id,
            auth()->user(),
            $transfer,
            'student.transfer.approved',
            ['status' => 'pending'],
            ['status' => $transfer->status],
            null,
            request()
        );

        return back()->with('success',
            'Transfer approved. Student has been moved to your school.');
    }

    public function reject(StudentTransfer $transfer)
    {
        // Only the RECEIVING school can reject
        abort_unless($transfer->to_tenant_id === auth()->user()->tenant_id, 403,
            'Only the receiving school can reject this transfer.');

        abort_if($transfer->status !== 'pending', 422,
            'This transfer has already been processed.');

        $transfer->update(['status' => 'rejected']);

        $this->auditLogger->record(
            auth()->user()->tenant_id,
            auth()->user(),
            $transfer,
            'student.transfer.rejected',
            ['status' => 'pending'],
            ['status' => 'rejected'],
            null,
            request()
        );

        return back()->with('success', 'Transfer request rejected.');
    }
}
