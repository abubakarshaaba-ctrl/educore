<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermlySummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ParentController extends Controller
{
    public function dashboard(Request $request)
    {
        [$guardian, $students, $student] = $this->context($request);
        $term = Term::where('is_current', true)->first();
        $summary = $student && $term
            ? TermlySummary::where('student_id', $student->id)->where('term_id', $term->id)->first()
            : null;

        $records = $student && $term
            ? AttendanceRecord::where('student_id', $student->id)->where('term_id', $term->id)->get(['status'])
            : collect();
        $present = $records->where('status', 'present')->count();
        $rate = $records->isNotEmpty() ? round(($present / $records->count()) * 100, 1) : 0;

        $outstanding = $student
            ? (float) (Invoice::where('student_id', $student->id)
                ->where('status', '!=', 'paid')
                ->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) as balance')
                ->value('balance') ?? 0)
            : 0;

        $announcements = Announcement::where('is_published', true)
            ->whereIn('audience', ['all', 'parents'])
            ->where(fn ($query) => $query->whereNull('expire_date')->orWhere('expire_date', '>=', now()->toDateString()))
            ->latest(Schema::hasColumn('announcements', 'publish_date') ? 'publish_date' : 'created_at')
            ->limit(6)
            ->get(['id', 'title', 'body', 'priority', 'publish_date']);

        return response()->json([
            'guardian' => ['id' => $guardian->id, 'name' => $guardian->full_name, 'relationship' => $guardian->relationship],
            'children' => $students->map(fn (Student $child) => $this->studentPayload($child)),
            'selected_child' => $student ? $this->studentPayload($student) : null,
            'current_term' => $term?->only(['id', 'name']),
            'result' => $summary ? [
                'average' => $summary->final_average,
                'position' => $summary->position_in_class,
                'class_size' => $summary->total_students_in_class,
            ] : null,
            'attendance' => ['total' => $records->count(), 'present' => $present, 'rate' => $rate],
            'outstanding_balance' => $outstanding,
            'announcements' => $announcements,
        ]);
    }

    public function invoices(Request $request)
    {
        [, , $student] = $this->context($request);
        $invoices = $student
            ? Invoice::with(['term.session'])
                ->where('student_id', $student->id)
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'number' => $invoice->invoice_number,
                    'term' => $invoice->term?->name,
                    'session' => $invoice->session?->name,
                    'total_amount' => $invoice->total_amount,
                    'amount_paid' => $invoice->amount_paid,
                    'balance' => $invoice->balance,
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date,
                ])
            : collect();

        return response()->json(['invoices' => $invoices]);
    }

    public function results(Request $request)
    {
        [, , $student] = $this->context($request);
        $results = $student
            ? TermlySummary::with(['term.session'])
                ->where('student_id', $student->id)
                ->latest('computed_at')
                ->get()
                ->map(fn (TermlySummary $summary) => [
                    'id' => $summary->id,
                    'term' => $summary->term?->name,
                    'session' => $summary->term?->session?->name,
                    'average' => $summary->final_average,
                    'position' => $summary->position_in_class,
                    'class_size' => $summary->total_students_in_class,
                    'subjects_offered' => $summary->subjects_offered,
                    'subjects_failed' => $summary->subjects_failed,
                    'promotion_status' => $summary->promotion_status,
                ])
            : collect();

        return response()->json(['results' => $results]);
    }

    public function attendance(Request $request)
    {
        [, , $student] = $this->context($request);
        $term = Term::where('is_current', true)->first();
        $records = $student
            ? AttendanceRecord::where('student_id', $student->id)
                ->when($term, fn ($query) => $query->where('term_id', $term->id))
                ->orderByDesc('attendance_date')
                ->limit(100)
                ->get(['attendance_date', 'status', 'remark'])
            : collect();

        return response()->json([
            'records' => $records,
            'stats' => [
                'total' => $records->count(),
                'present' => $records->where('status', 'present')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'late' => $records->where('status', 'late')->count(),
            ],
        ]);
    }

    private function context(Request $request): array
    {
        $user = $request->user();
        abort_unless($user?->isParent(), 403, 'Parent portal access only.');

        $guardian = Guardian::where('user_id', $user->id)->first();
        abort_unless($guardian, 403, 'No guardian profile is linked to this account.');
        abort_unless((int) $guardian->tenant_id === (int) $user->tenant_id, 403, 'Guardian profile is outside your school.');

        $students = $guardian->students()
            ->with(['currentClassArm.classLevel'])
            ->where('students.tenant_id', $user->tenant_id)
            ->orderBy('first_name')
            ->get();

        $student = $students->first();
        if ($request->filled('child_id')) {
            $student = $students->firstWhere('id', (int) $request->integer('child_id'));
            abort_unless($student, 403, 'This child is not linked to your parent account.');
        }

        return [$guardian, $students, $student];
    }

    private function studentPayload(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->full_name,
            'admission_number' => $student->admission_number,
            'status' => $student->status,
            'class' => $student->currentClassArm ? [
                'id' => $student->currentClassArm->id,
                'name' => trim(($student->currentClassArm->classLevel?->name ?? '') . ' ' . $student->currentClassArm->name),
            ] : null,
        ];
    }
}
