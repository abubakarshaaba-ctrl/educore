<?php

namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\Student;
use App\Models\StudentStatusHistory;
use App\Services\StudentLifecycle\ChangeStudentStatus;
use App\Services\StudentLifecycle\CorrectGraduatedStudentStatus;
use App\Services\StudentLifecycle\ReactivateStudent;
use App\Services\StudentLifecycle\ReadmitStudent;
use App\Services\StudentLifecycle\StudentLifecycleRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class StudentLifecycleController extends Controller
{
    public function showStatus(Student $student): View
    {
        $this->authorizePermission('student.status.view');

        $student = $this->tenantStudent($student)
            ->load(['currentClassArm.classLevel', 'statusHistories.changedBy', 'statusHistories.approvedBy']);

        return view('students.status.show', [
            'student' => $student,
            'allowedDestinations' => StudentLifecycleRules::allowedDirectDestinations($student->status),
            'statusLabels' => StudentLifecycleRules::statusLabels(),
            'histories' => $student->statusHistories()->with(['changedBy', 'approvedBy'])->latest()->get(),
        ]);
    }

    public function updateStatus(
        Request $request,
        Student $student,
        ChangeStudentStatus $changeStudentStatus
    ): RedirectResponse {
        $this->authorizePermission('student.status.change');
        $this->authorizePermission('student.status.approve');

        $student = $this->tenantStudent($student);
        $allowedDestinations = StudentLifecycleRules::allowedDirectDestinations($student->status);

        $data = $request->validate([
            'new_status' => ['required', Rule::in($allowedDestinations)],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'destination_school' => [
                Rule::requiredIf($request->input('new_status') === Student::STATUS_TRANSFERRED_OUT),
                'nullable',
                'string',
                'max:255',
            ],
            'transfer_certificate_number' => ['nullable', 'string', 'max:100'],
            'confirmation' => ['exclude_unless:new_status,' . Student::STATUS_GRADUATED, 'accepted'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $student = $this->runLifecycleActionWithDocument(
            $request,
            $data,
            fn (array $payload) => $changeStudentStatus->execute(auth()->user(), $student, $payload, $request)
        );

        return redirect()
            ->route($student->isArchivedLifecycleStatus() ? 'students.archive.show' : 'students.show', $student)
            ->with('success', 'Student lifecycle status updated.');
    }

    public function reactivateForm(Student $student): View
    {
        $this->authorizePermission('student.reactivate');

        $student = $this->tenantStudent($student)->load('currentClassArm.classLevel');

        abort_unless(StudentLifecycleRules::canReactivate($student->status), 404);

        return view('students.archive.reactivate', [
            'student' => $student,
            'classArms' => $this->classArmOptions(),
            'requiresClassArm' => $student->status !== Student::STATUS_SUSPENDED,
        ]);
    }

    public function reactivate(
        Request $request,
        Student $student,
        ReactivateStudent $reactivateStudent
    ): RedirectResponse {
        $this->authorizePermission('student.reactivate');

        $student = $this->tenantStudent($student);
        abort_unless(StudentLifecycleRules::canReactivate($student->status), 404);

        $data = $request->validate([
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'class_arm_id' => [
                Rule::requiredIf($student->status !== Student::STATUS_SUSPENDED),
                'nullable',
                Rule::exists('class_arms', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $student = $this->runLifecycleActionWithDocument(
            $request,
            $data,
            fn (array $payload) => $reactivateStudent->execute(auth()->user(), $student, $payload, $request)
        );

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Student reactivated.');
    }

    public function readmitForm(Student $student): View
    {
        $this->authorizePermission('student.readmit');

        $student = $this->tenantStudent($student)->load('currentClassArm.classLevel');
        abort_unless($student->status === Student::STATUS_TRANSFERRED_OUT, 404);

        return view('students.archive.readmit', [
            'student' => $student,
            'classArms' => $this->classArmOptions(),
        ]);
    }

    public function readmit(
        Request $request,
        Student $student,
        ReadmitStudent $readmitStudent
    ): RedirectResponse {
        $this->authorizePermission('student.readmit');

        $student = $this->tenantStudent($student);
        abort_unless($student->status === Student::STATUS_TRANSFERRED_OUT, 404);

        $data = $request->validate([
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'class_arm_id' => [
                'required',
                Rule::exists('class_arms', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $student = $this->runLifecycleActionWithDocument(
            $request,
            $data,
            fn (array $payload) => $readmitStudent->execute(auth()->user(), $student, $payload, $request)
        );

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Student readmitted.');
    }

    public function graduationCorrectionForm(Student $student): View
    {
        $this->authorizePermission('student.status.correct-graduation');

        $student = $this->tenantStudent($student)->load('currentClassArm.classLevel');
        abort_unless($student->status === Student::STATUS_GRADUATED, 404);

        return view('students.archive.graduation-correction', [
            'student' => $student,
            'classArms' => $this->classArmOptions(),
        ]);
    }

    public function correctGraduation(
        Request $request,
        Student $student,
        CorrectGraduatedStudentStatus $correctGraduatedStudentStatus
    ): RedirectResponse {
        $this->authorizePermission('student.status.correct-graduation');

        $student = $this->tenantStudent($student);
        abort_unless($student->status === Student::STATUS_GRADUATED, 404);

        $data = $request->validate([
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'class_arm_id' => [
                'required',
                Rule::exists('class_arms', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'confirm_correction' => ['accepted'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $student = $this->runLifecycleActionWithDocument(
            $request,
            $data,
            fn (array $payload) => $correctGraduatedStudentStatus->execute(auth()->user(), $student, $payload, $request)
        );

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Graduation status corrected.');
    }

    public function downloadDocument(StudentStatusHistory $history)
    {
        abort_unless(
            auth()->user()?->can('student.status.view') || auth()->user()?->can('student.archive.view'),
            403
        );

        $history = StudentStatusHistory::where('tenant_id', $this->tenantId())
            ->whereKey($history->id)
            ->firstOrFail();

        abort_unless($history->document_path, 404);

        if (Storage::exists($history->document_path)) {
            return Storage::download($history->document_path);
        }

        abort(404);
    }

    private function storeLifecycleDocument(Request $request, int $tenantId): ?string
    {
        if (!$request->hasFile('document')) {
            return null;
        }

        $file = $request->file('document');
        $extension = $file->extension();

        return $file->storeAs(
            'student-lifecycle/' . $tenantId,
            Str::uuid()->toString() . ($extension ? '.' . $extension : '')
        );
    }

    /**
     * Store an optional lifecycle document, and remove it if the database
     * transaction rejects the lifecycle action.
     */
    private function runLifecycleActionWithDocument(Request $request, array $data, callable $callback): Student
    {
        $documentPath = $this->storeLifecycleDocument($request, $this->tenantId());
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

    private function tenantStudent(Student $student): Student
    {
        return Student::where('tenant_id', $this->tenantId())
            ->whereKey($student->id)
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

    private function classArmOptions()
    {
        return ClassArm::with('classLevel')
            ->where('tenant_id', $this->tenantId())
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();
    }
}
