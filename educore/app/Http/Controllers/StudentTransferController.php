<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\Tenant;
use App\Services\LifecycleAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $tenants = Tenant::where('id', '!=', $tenantId)
                        ->where('status', 'active')
                        ->get();

        $activeStudents = Student::where('status', 'active')
                            ->orderBy('last_name')
                            ->get();

        return view('students.transfers',
            compact('outgoing', 'incoming', 'tenants', 'activeStudents'));
    }

    public function request(Request $request)
    {
        $tenantId = (int) auth()->user()->tenant_id;
        $data = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('status', Student::STATUS_ACTIVE)
                    ->whereNull('deleted_at')),
            ],
            'to_tenant_id' => [
                'required',
                Rule::exists('tenants', 'id')->where(fn ($query) => $query
                    ->where('status', 'active')
                    ->where('id', '!=', $tenantId)
                    ->whereNull('deleted_at')),
            ],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $student = Student::findOrFail($data['student_id']);

        abort_if((int) $student->tenant_id !== $tenantId, 403,
            'You can only transfer students from your own school.');

        $existing = StudentTransfer::where('student_id', $student->id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return back()->withErrors([
                'student_id' => 'This student already has a pending transfer request.'
            ]);
        }

        $transfer = StudentTransfer::create([
            'from_tenant_id'   => $tenantId,
            'to_tenant_id'     => $data['to_tenant_id'],
            'student_id'       => $student->id,
            'student_name'     => $student->full_name,
            'admission_number' => $student->admission_number ?? null,
            'reason'           => $data['reason'] ?? null,
            'requested_by'     => auth()->id(),
            'status'           => 'pending',
        ]);

        $this->auditLogger->record(
            $tenantId,
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
        $receiverTenantId = (int) auth()->user()->tenant_id;

        $transfer = DB::transaction(function () use ($transfer, $receiverTenantId): StudentTransfer {
            $locked = StudentTransfer::whereKey($transfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless((int) $locked->to_tenant_id === $receiverTenantId, 403,
                'Only the receiving school can approve this transfer.');
            abort_if($locked->status !== 'pending', 422,
                'This transfer has already been processed.');

            // Re-check ownership while both transfer and student are locked. A
            // stale pending request must never move a student who has meanwhile
            // left the originating school through another lifecycle action.
            $student = Student::withoutTenantScope()
                ->whereKey($locked->student_id)
                ->where('tenant_id', $locked->from_tenant_id)
                ->lockForUpdate()
                ->first();

            abort_unless($student, 409,
                'This student no longer belongs to the originating school. The transfer cannot be completed.');

            $student->forceFill([
                'tenant_id' => $locked->to_tenant_id,
                'current_class_arm_id' => null,
                'status' => Student::STATUS_ACTIVE,
            ])->save();

            $locked->update([
                'status' => 'completed',
                'approved_at' => now(),
            ]);

            return $locked->fresh();
        });

        $this->auditLogger->record(
            $receiverTenantId,
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
        $receiverTenantId = (int) auth()->user()->tenant_id;

        $transfer = DB::transaction(function () use ($transfer, $receiverTenantId): StudentTransfer {
            $locked = StudentTransfer::whereKey($transfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless((int) $locked->to_tenant_id === $receiverTenantId, 403,
                'Only the receiving school can reject this transfer.');
            abort_if($locked->status !== 'pending', 422,
                'This transfer has already been processed.');

            $locked->update(['status' => 'rejected']);

            return $locked->fresh();
        });

        $this->auditLogger->record(
            $receiverTenantId,
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
