<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\Tenant;
use App\Services\CrossSchoolStudentTransferService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentTransferController extends Controller
{
    public function __construct(private readonly CrossSchoolStudentTransferService $transfers)
    {
    }

    public function index()
    {
        $tenantId = (int) auth()->user()->tenant_id;

        $outgoing = StudentTransfer::where('from_tenant_id', $tenantId)
            ->latest()->get();

        $incoming = StudentTransfer::where('to_tenant_id', $tenantId)
            ->latest()->get();

        $tenants = Tenant::where('id', '!=', $tenantId)
            ->where('status', Tenant::STATUS_ACTIVE)
            ->get();

        $activeStudents = Student::where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
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
                    ->where('status', Tenant::STATUS_ACTIVE)
                    ->where('id', '!=', $tenantId)
                    ->whereNull('deleted_at')),
            ],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->transfers->request(
            auth()->user(),
            (int) $data['student_id'],
            (int) $data['to_tenant_id'],
            $data['reason'] ?? null,
            $request,
        );

        return back()->with('success',
            'Transfer request submitted. Awaiting approval from the receiving school.');
    }

    public function approve(StudentTransfer $transfer)
    {
        $completed = $this->transfers->approve(
            auth()->user(),
            (int) $transfer->id,
            request(),
        );

        return back()->with('success',
            'Transfer approved. A new student record #'.$completed->destination_student_id.' has been created for your school; the source school history remains archived there.');
    }

    public function reject(StudentTransfer $transfer)
    {
        $this->transfers->reject(
            auth()->user(),
            (int) $transfer->id,
            request(),
        );

        return back()->with('success', 'Transfer request rejected.');
    }
}
