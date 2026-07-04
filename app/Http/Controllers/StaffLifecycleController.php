<?php

namespace App\Http\Controllers;

use App\Models\StaffStatusHistory;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\StaffSalarySetting;
use App\Models\TimetablePeriod;
use App\Models\TransportRoute;
use App\Models\User;
use App\Services\StaffLifecycle\ChangeStaffStatus;
use App\Services\StaffLifecycle\ReinstateStaff;
use App\Services\StaffLifecycle\StaffLifecycleRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class StaffLifecycleController extends Controller
{
    public function showStatus(User $staff): View
    {
        $this->authorizePermission('staff.status.view');

        $staff = $this->tenantStaff($staff)
            ->load(['staffStatusHistories.changedBy', 'staffStatusHistories.approvedBy', 'currentWorkHistory']);

        return view('staff.status.show', [
            'staff' => $staff,
            'allowedDestinations' => StaffLifecycleRules::allowedExitDestinations($staff->employmentStatus()),
            'statusLabels' => StaffLifecycleRules::statusLabels(),
            'histories' => $staff->staffStatusHistories()->with(['changedBy', 'approvedBy'])->latest()->get(),
            'impactSummary' => $this->impactSummary($staff),
        ]);
    }

    public function updateStatus(
        Request $request,
        User $staff,
        ChangeStaffStatus $changeStaffStatus
    ): RedirectResponse {
        $this->authorizePermission('staff.status.change');
        $this->authorizePermission('staff.status.approve');

        $staff = $this->tenantStaff($staff);
        $allowedDestinations = StaffLifecycleRules::allowedExitDestinations($staff->employmentStatus());

        $data = $request->validate([
            'new_status' => ['required', Rule::in($allowedDestinations)],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'confirmation' => ['accepted'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $staff = $this->runLifecycleActionWithDocument(
            $request,
            $data,
            fn (array $payload) => $changeStaffStatus->execute(auth()->user(), $staff, $payload, $request)
        );

        if ($staff->isArchivedStaffStatus()) {
            $route = auth()->user()?->can('staff.archive.view')
                ? route('staff.archive.show', $staff)
                : route('staff.index');

            return redirect($route)->with('success', 'Staff lifecycle status updated.');
        }

        return redirect()
            ->route('staff.show', $staff)
            ->with('success', 'Staff lifecycle status updated.');
    }

    public function reinstateForm(User $staff): View
    {
        $this->authorizePermission('staff.reinstate');

        $staff = $this->tenantStaff($staff)->load(['currentWorkHistory']);

        abort_unless(StaffLifecycleRules::canReinstate($staff->employmentStatus()), 404);

        return view('staff.archive.reinstate', compact('staff'));
    }

    public function reinstate(
        Request $request,
        User $staff,
        ReinstateStaff $reinstateStaff
    ): RedirectResponse {
        $this->authorizePermission('staff.reinstate');

        $staff = $this->tenantStaff($staff);
        abort_unless(StaffLifecycleRules::canReinstate($staff->employmentStatus()), 404);

        $data = $request->validate([
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'position_title' => ['required', 'string', 'max:255'],
            'department_name' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'functional_role' => ['nullable', 'string', 'max:150'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'appointment_type' => ['nullable', 'string', 'max:100'],
            'confirmation' => ['accepted'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $staff = $this->runLifecycleActionWithDocument(
            $request,
            $data,
            fn (array $payload) => $reinstateStaff->execute(auth()->user(), $staff, $payload, $request)
        );

        return redirect()
            ->route('staff.show', $staff)
            ->with('success', 'Staff member reinstated.');
    }

    public function downloadStatusDocument(StaffStatusHistory $history)
    {
        abort_unless(
            auth()->user()?->can('staff.status.view') || auth()->user()?->can('staff.archive.view'),
            403
        );

        $history = StaffStatusHistory::where('tenant_id', $this->tenantId())
            ->whereKey($history->id)
            ->firstOrFail();

        abort_unless($history->document_path, 404);

        if (Storage::exists($history->document_path)) {
            return Storage::download($history->document_path);
        }

        abort(404);
    }

    private function storeLifecycleDocument(Request $request): ?string
    {
        if (!$request->hasFile('document')) {
            return null;
        }

        $file = $request->file('document');
        $extension = $file->extension();

        return $file->storeAs(
            'staff-lifecycle/' . $this->tenantId(),
            Str::uuid()->toString() . ($extension ? '.' . $extension : '')
        );
    }

    private function runLifecycleActionWithDocument(Request $request, array $data, callable $callback): User
    {
        $documentPath = $this->storeLifecycleDocument($request);
        $data['document_path'] = $documentPath;

        try {
            return $callback($data);
        } catch (Throwable $exception) {
            if ($documentPath) {
                Storage::delete($documentPath);
            }

            throw $exception;
        }
    }

    private function tenantStaff(User $staff): User
    {
        return User::tenantStaff($this->tenantId())
            ->whereKey($staff->id)
            ->firstOrFail();
    }

    private function impactSummary(User $staff): array
    {
        $tenantId = $this->tenantId();

        return [
            'salary_settings' => StaffSalarySetting::where('tenant_id', $tenantId)
                ->where('staff_id', $staff->id)
                ->where('is_active', true)
                ->count(),
            'class_teacher_assignments' => ClassArm::where('tenant_id', $tenantId)
                ->where('form_tutor_id', $staff->id)
                ->count(),
            'subject_assignments' => ClassArmSubject::where('tenant_id', $tenantId)
                ->where('teacher_id', $staff->id)
                ->where('is_active', true)
                ->count(),
            'timetable_periods' => TimetablePeriod::where('tenant_id', $tenantId)
                ->where('teacher_id', $staff->id)
                ->count(),
            'transport_assignments' => TransportRoute::where('tenant_id', $tenantId)
                ->where(function ($query) use ($staff) {
                    $query->where('driver_id', $staff->id)
                        ->orWhere('assistant_id', $staff->id);
                })
                ->where('is_active', true)
                ->count(),
        ];
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
