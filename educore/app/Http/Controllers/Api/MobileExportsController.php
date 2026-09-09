<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileExportsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School export access required.');
        abort_unless($user->canAccessModule('exports'), 403, 'You do not have access to exports.');

        if ($request->query('action') === 'download') return $this->download($request, $user);

        $tenantId = (int) $user->tenant_id;
        $canStudents = $user->canAccessModule('students');
        $canBroadsheet = $this->canBroadsheet($user);
        $canFees = $user->canAccessModule('fees');
        $classes = ($canStudents || $canBroadsheet) ? ClassArm::query()->where('tenant_id', $tenantId)->with('classLevel:id,name')->get()->sortBy('full_name')->values()->map(fn (ClassArm $arm) => ['id' => $arm->id, 'name' => $arm->full_name]) : collect();
        $terms = $canBroadsheet ? Term::query()->where('tenant_id', $tenantId)->with('session:id,name')->latest('start_date')->get()->map(fn (Term $term) => ['id' => $term->id, 'name' => $term->name, 'session_id' => $term->session_id, 'session_name' => $term->session?->name, 'current' => (bool) $term->is_current]) : collect();
        $sessions = $canFees ? AcademicSession::query()->where('tenant_id', $tenantId)->latest()->get()->map(fn (AcademicSession $session) => ['id' => $session->id, 'name' => $session->name, 'current' => (bool) $session->is_current]) : collect();

        return response()->json([
            'contract_version' => 1,
            'module' => ['key' => 'exports', 'title' => 'Exports', 'description' => 'Generate tenant-scoped CSV reports for authorized school data', 'mobile_policy' => 'native_download'],
            'capabilities' => ['students' => $canStudents, 'broadsheet' => $canBroadsheet, 'fees' => $canFees],
            'classes' => $classes, 'terms' => $terms, 'sessions' => $sessions, 'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function download(Request $request, User $user): StreamedResponse
    {
        $data = $request->validate(['type' => ['required', Rule::in(['students', 'broadsheet', 'fees'])]]);
        return match ($data['type']) {
            'students' => $this->studentsCsv($request, $user),
            'broadsheet' => $this->broadsheetCsv($request, $user),
            'fees' => $this->feesCsv($request, $user),
        };
    }

    private function studentsCsv(Request $request, User $user): StreamedResponse
    {
        abort_unless($user->canAccessModule('students'), 403, 'Student export access required.');
        $tenantId = (int) $user->tenant_id;
        $validated = $request->validate(['class_arm_id' => ['nullable', 'integer', Rule::exists('class_arms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))]]);
        $students = Student::query()->where('tenant_id', $tenantId)->with(['currentClassArm.classLevel'])
            ->when($validated['class_arm_id'] ?? null, fn ($query, $classId) => $query->where('current_class_arm_id', $classId))
            ->where('status', Student::STATUS_ACTIVE)->orderBy('last_name')->orderBy('first_name')->get();
        return $this->csv('Students_'.now()->format('Y-m-d').'.csv', ['Admission No', 'Last Name', 'First Name', 'Gender', 'DOB', 'Class', 'Status'], function ($file) use ($students): void {
            foreach ($students as $student) fputcsv($file, [$student->admission_number, $student->last_name, $student->first_name, $student->gender, $student->date_of_birth, trim(($student->currentClassArm?->classLevel?->name ?? '').' '.($student->currentClassArm?->name ?? '')), $student->status]);
        });
    }

    private function broadsheetCsv(Request $request, User $user): StreamedResponse
    {
        abort_unless($this->canBroadsheet($user), 403, 'Broadsheet export access required.');
        $tenantId = (int) $user->tenant_id;
        $validated = $request->validate([
            'class_arm_id' => ['required', 'integer', Rule::exists('class_arms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'term_id' => ['required', 'integer', Rule::exists('terms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
        ]);
        $classArm = ClassArm::query()->where('tenant_id', $tenantId)->with('classLevel:id,name')->findOrFail($validated['class_arm_id']);
        $term = Term::query()->where('tenant_id', $tenantId)->findOrFail($validated['term_id']);
        $summaries = TermlySummary::query()->where('tenant_id', $tenantId)->where('class_arm_id', $classArm->id)->where('term_id', $term->id)->with('student')->orderBy('position_in_class')->get();
        $filename = 'Broadsheet_'.$this->filenamePart($classArm->full_name).'_'.$this->filenamePart($term->name).'.csv';
        return $this->csv($filename, ['Position', 'Student Name', 'Adm No', 'Total Score', 'Average', 'Subjects Offered', 'Subjects Failed', 'Status'], function ($file) use ($summaries): void {
            foreach ($summaries as $summary) {
                $average = (float) $summary->final_average;
                $status = $average >= 75 ? 'Distinction' : ($average >= 60 ? 'Merit' : ($average >= 50 ? 'Credit' : 'Below Average'));
                fputcsv($file, [$summary->position_in_class, $summary->student?->full_name, $summary->student?->admission_number, $summary->total_score, $average, $summary->subjects_offered, $summary->subjects_failed, $status]);
            }
        });
    }

    private function feesCsv(Request $request, User $user): StreamedResponse
    {
        abort_unless($user->canAccessModule('fees'), 403, 'Fee export access required.');
        $tenantId = (int) $user->tenant_id;
        $validated = $request->validate(['session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))]]);
        $invoices = Invoice::query()->where('tenant_id', $tenantId)->with('student')->when($validated['session_id'] ?? null, fn ($query, $sessionId) => $query->where('session_id', $sessionId))->orderBy('invoice_number')->get();
        return $this->csv('Fees_Report_'.now()->format('Y-m-d').'.csv', ['Invoice No', 'Student', 'Amount Billed', 'Amount Paid', 'Balance', 'Status'], function ($file) use ($invoices): void {
            foreach ($invoices as $invoice) fputcsv($file, [$invoice->invoice_number, $invoice->student?->full_name, $invoice->total_amount, $invoice->amount_paid, max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid), $invoice->status]);
        });
    }

    private function csv(string $filename, array $header, callable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $file = fopen('php://output', 'w');
            if ($file === false) return;
            fputcsv($file, $header); $rows($file); fclose($file);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function canBroadsheet(User $user): bool
    {
        return $user->canAccessModule('scores') || $user->canAccessModule('scores.view') || $user->canAccessModule('reports');
    }

    private function filenamePart(string $value): string
    {
        return Str::of($value)->ascii()->replaceMatches('/[^A-Za-z0-9_-]+/', '_')->trim('_')->limit(60, '')->toString();
    }
}
