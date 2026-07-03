<?php

namespace App\Http\Controllers;

use App\Models\StaffWorkHistory;
use App\Models\User;
use App\Services\StaffLifecycle\RecordStaffWorkHistory;
use App\Services\StaffLifecycle\StaffLifecycleRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class StaffWorkHistoryController extends Controller
{
    public function index(User $staff): View
    {
        $this->authorizePermission('staff.work-history.view');

        $staff = $this->tenantStaff($staff)
            ->load(['workHistories.recordedBy', 'workHistories.approvedBy', 'currentWorkHistory']);

        return view('staff.work-history.index', [
            'staff' => $staff,
            'histories' => $staff->workHistories()
                ->with(['recordedBy', 'approvedBy'])
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get(),
            'changeTypes' => StaffLifecycleRules::EXPOSED_WORK_CHANGE_TYPES,
        ]);
    }

    public function store(
        Request $request,
        User $staff,
        RecordStaffWorkHistory $recordStaffWorkHistory
    ): RedirectResponse {
        $this->authorizePermission('staff.work-history.manage');
        $this->authorizePermission('staff.work-history.approve');

        $staff = $this->tenantStaff($staff);

        $data = $request->validate([
            'position_title' => ['required', 'string', 'max:255'],
            'department_name' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'functional_role' => ['nullable', 'string', 'max:150'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'appointment_type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'change_type' => ['required', Rule::in(StaffLifecycleRules::EXPOSED_WORK_CHANGE_TYPES)],
            'reason' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $this->runWithDocument(
            $request,
            $data,
            fn (array $payload) => $recordStaffWorkHistory->execute(auth()->user(), $staff, $payload, $request)
        );

        return redirect()
            ->route('staff.work-history.index', $staff)
            ->with('success', 'Staff work history recorded.');
    }

    public function show(StaffWorkHistory $history): View
    {
        $this->authorizePermission('staff.work-history.view');

        $history = StaffWorkHistory::where('tenant_id', $this->tenantId())
            ->whereKey($history->id)
            ->with(['staff', 'recordedBy', 'approvedBy'])
            ->firstOrFail();

        return view('staff.work-history.show', compact('history'));
    }

    public function downloadDocument(StaffWorkHistory $history)
    {
        $this->authorizePermission('staff.work-history.view');

        $history = StaffWorkHistory::where('tenant_id', $this->tenantId())
            ->whereKey($history->id)
            ->firstOrFail();

        abort_unless($history->document_path, 404);

        if (Storage::exists($history->document_path)) {
            return Storage::download($history->document_path);
        }

        abort(404);
    }

    private function runWithDocument(Request $request, array $data, callable $callback): mixed
    {
        $documentPath = $this->storeWorkHistoryDocument($request);
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

    private function storeWorkHistoryDocument(Request $request): ?string
    {
        if (!$request->hasFile('document')) {
            return null;
        }

        $file = $request->file('document');
        $extension = $file->extension();

        return $file->storeAs(
            'staff-work-history/' . $this->tenantId(),
            Str::uuid()->toString() . ($extension ? '.' . $extension : '')
        );
    }

    private function tenantStaff(User $staff): User
    {
        return User::tenantStaff($this->tenantId())
            ->whereKey($staff->id)
            ->firstOrFail();
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
