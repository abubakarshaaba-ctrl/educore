<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassArm;
use App\Models\ReportCardPublication;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use App\Services\ReportCardComputationService;
use App\Services\ReportCardDocumentService;
use App\Services\ReportCardPublicationService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->guardView($request);
        $tenantId = (int) $user->tenant_id;

        $classes = ClassArm::where('tenant_id', $tenantId)
            ->with('classLevel:id,name')
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();
        $terms = Term::where('tenant_id', $tenantId)
            ->with('session:id,name')
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get();

        $classArm = $request->filled('class_arm_id')
            ? $classes->firstWhere('id', (int) $request->integer('class_arm_id'))
            : $classes->first();
        $term = $request->filled('term_id')
            ? $terms->firstWhere('id', (int) $request->integer('term_id'))
            : ($terms->firstWhere('is_current', true) ?? $terms->first());

        if ($request->filled('class_arm_id')) {
            abort_unless($classArm, 422, 'The selected class is not available for this school.');
        }
        if ($request->filled('term_id')) {
            abort_unless($term, 422, 'The selected term is not available for this school.');
        }

        $summaries = collect();
        $publication = null;
        $activeStudents = 0;
        if ($classArm && $term) {
            $activeStudents = Student::where('tenant_id', $tenantId)
                ->where('current_class_arm_id', $classArm->id)
                ->where('status', Student::STATUS_ACTIVE)
                ->count();
            $summaries = TermlySummary::where('tenant_id', $tenantId)
                ->where('class_arm_id', $classArm->id)
                ->where('term_id', $term->id)
                ->with('student:id,first_name,middle_name,last_name,admission_number')
                ->orderBy('position_in_class')
                ->orderBy('student_id')
                ->get();
            $publication = ReportCardPublication::where('tenant_id', $tenantId)
                ->where('class_arm_id', $classArm->id)
                ->where('term_id', $term->id)
                ->with('publishedBy:id,name')
                ->first();
        }

        $canManage = $this->canManage($user);

        return response()->json([
            'contract_version' => 1,
            'module' => ['key' => 'reports', 'title' => 'Report Cards'],
            'capabilities' => [
                'view' => true,
                'compute' => $canManage,
                'publish' => $canManage,
                'unpublish' => $canManage,
                'edit_remarks' => $canManage || $user->canAccessExactModule('reports.remarks'),
            ],
            'options' => [
                'class_arms' => $classes->map(fn (ClassArm $item) => [
                    'id' => $item->id,
                    'name' => $item->full_name,
                    'class_level_id' => $item->class_level_id,
                ])->values(),
                'terms' => $terms->map(fn (Term $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'session' => $item->session?->name,
                    'is_current' => (bool) $item->is_current,
                ])->values(),
            ],
            'selected' => [
                'class_arm_id' => $classArm?->id,
                'term_id' => $term?->id,
            ],
            'class' => $classArm ? ['id' => $classArm->id, 'name' => $classArm->full_name] : null,
            'term' => $term ? [
                'id' => $term->id,
                'name' => $term->name,
                'session' => $term->session?->name,
            ] : null,
            'publication' => $publication ? $this->publicationPayload($publication) : null,
            'summary' => [
                'computed' => $summaries->count(),
                'active_students' => $activeStudents,
                'missing' => max(0, $activeStudents - $summaries->count()),
                'published' => (bool) ($publication?->isPublished()),
            ],
            'students' => $summaries->map(fn (TermlySummary $summary) => $this->summaryPayload($summary))->values(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function compute(Request $request, ReportCardComputationService $reports)
    {
        $user = $this->guardManage($request);
        $data = $this->selection($request);
        $this->assertSelection((int) $user->tenant_id, $data['class_arm_id'], $data['term_id']);

        $computed = $reports->compute((int) $user->tenant_id, $data['class_arm_id'], $data['term_id']);

        return response()->json([
            'message' => 'Report cards computed successfully.',
            'computed' => $computed,
        ]);
    }

    public function publish(Request $request, ReportCardPublicationService $publications)
    {
        $user = $this->guardManage($request);
        $data = $this->selection($request, true);
        $this->assertSelection((int) $user->tenant_id, $data['class_arm_id'], $data['term_id']);

        $result = $publications->publish(
            (int) $user->tenant_id,
            $data['class_arm_id'],
            $data['term_id'],
            $user,
            $data['note'] ?? null,
            $request,
        );

        return response()->json([
            'message' => 'Report cards published successfully.',
            'publication' => $this->publicationPayload($result['publication']),
            'guardians_notified' => $result['guardians_notified'],
        ]);
    }

    public function unpublish(Request $request, ReportCardPublicationService $publications)
    {
        $user = $this->guardManage($request);
        $data = $this->selection($request);
        $this->assertSelection((int) $user->tenant_id, $data['class_arm_id'], $data['term_id']);

        $publication = $publications->unpublish(
            (int) $user->tenant_id,
            $data['class_arm_id'],
            $data['term_id'],
            $user,
            $request,
        );

        return response()->json([
            'message' => 'Report cards returned to draft.',
            'publication' => $this->publicationPayload($publication->fresh(['publishedBy:id,name'])),
        ]);
    }

    public function pdf(Request $request, TermlySummary $summary, ReportCardDocumentService $documents)
    {
        $user = $this->guardView($request);
        abort_unless((int) $summary->tenant_id === (int) $user->tenant_id, 404);

        return $documents->download((int) $user->tenant_id, (int) $summary->id);
    }

    private function guardView(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isAccountant(), 403, 'Accountants cannot access academic report cards.');
        abort_unless(
            $this->canManage($user)
                || $user->canAccessExactModule('reports.view')
                || $user->canAccessExactModule('reports.remarks'),
            403,
            'You do not have permission to access report cards.'
        );

        return $user;
    }

    private function guardManage(Request $request): User
    {
        $user = $this->guardView($request);
        abort_unless($this->canManage($user), 403, 'You do not have permission to compute or publish report cards.');

        return $user;
    }

    private function canManage(User $user): bool
    {
        return $user->isSuperAdmin() || $user->canAccessExactModule('reports');
    }

    private function selection(Request $request, bool $allowNote = false): array
    {
        $rules = [
            'class_arm_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
        ];
        if ($allowNote) {
            $rules['note'] = ['nullable', 'string', 'max:1000'];
        }

        return $request->validate($rules);
    }

    private function assertSelection(int $tenantId, int $classArmId, int $termId): void
    {
        abort_unless(ClassArm::where('tenant_id', $tenantId)->whereKey($classArmId)->exists(), 422, 'The selected class is not available for this school.');
        abort_unless(Term::where('tenant_id', $tenantId)->whereKey($termId)->exists(), 422, 'The selected term is not available for this school.');
    }

    private function publicationPayload(ReportCardPublication $publication): array
    {
        return [
            'id' => $publication->id,
            'status' => $publication->status,
            'published_at' => $publication->published_at?->toIso8601String(),
            'published_by' => $publication->published_by,
            'published_by_name' => $publication->publishedBy?->name,
            'archived_at' => $publication->archived_at?->toIso8601String(),
            'note' => $publication->note,
        ];
    }

    private function summaryPayload(TermlySummary $summary): array
    {
        $student = $summary->student;
        $subjects = collect($summary->subject_breakdown ?? [])->map(fn ($row) => [
            'subject_id' => isset($row['subject_id']) ? (int) $row['subject_id'] : null,
            'subject' => (string) ($row['subject'] ?? 'Subject'),
            'total' => (float) ($row['total'] ?? 0),
            'grade' => $row['grade'] ?? null,
            'remark' => $row['remark'] ?? null,
            'is_pass' => (bool) ($row['is_pass'] ?? false),
            'position' => isset($row['position']) ? (int) $row['position'] : null,
            'class_highest' => isset($row['class_highest']) ? (float) $row['class_highest'] : null,
            'class_lowest' => isset($row['class_lowest']) ? (float) $row['class_lowest'] : null,
            'class_avg' => isset($row['class_avg']) ? (float) $row['class_avg'] : null,
            'annual_total' => isset($row['annual_total']) ? (float) $row['annual_total'] : null,
            'cumulative_avg' => isset($row['cumulative_avg']) ? (float) $row['cumulative_avg'] : null,
            'term_totals' => $row['term_totals'] ?? null,
        ])->values();

        return [
            'summary_id' => $summary->id,
            'student' => [
                'id' => $student?->id ?? $summary->student_id,
                'name' => $student?->full_name ?? 'Student',
                'admission_number' => $student?->admission_number,
            ],
            'average' => (float) ($summary->final_average ?? 0),
            'position' => $summary->position_in_class,
            'class_size' => $summary->total_students_in_class,
            'subjects_offered' => (int) ($summary->subjects_offered ?? 0),
            'subjects_failed' => (int) ($summary->subjects_failed ?? 0),
            'promotion_status' => $summary->promotion_status,
            'form_tutor_remark' => $summary->form_tutor_remark,
            'principal_remark' => $summary->principal_remark,
            'subjects' => $subjects,
            'computed_at' => $summary->computed_at?->toIso8601String(),
        ];
    }
}
