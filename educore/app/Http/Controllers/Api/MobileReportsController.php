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
use App\Services\ReportCardPublicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileReportsController extends Controller
{
    public function __construct(
        private readonly ReportCardComputationService $computation,
        private readonly ReportCardPublicationService $publication,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'class_arm_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer'],
        ]);

        $classArms = ClassArm::where('tenant_id', $tenantId)
            ->with('classLevel:id,name')
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();
        $terms = Term::where('tenant_id', $tenantId)
            ->with('session:id,name')
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $classArm = null;
        $term = null;
        if (isset($data['class_arm_id'])) {
            $classArm = ClassArm::where('tenant_id', $tenantId)
                ->with('classLevel:id,name')
                ->whereKey((int) $data['class_arm_id'])
                ->firstOrFail();
        }
        if (isset($data['term_id'])) {
            $term = Term::where('tenant_id', $tenantId)
                ->with('session:id,name')
                ->whereKey((int) $data['term_id'])
                ->firstOrFail();
        }

        $summaries = collect();
        $publication = null;
        $activeStudents = null;
        if ($classArm && $term) {
            $summaries = TermlySummary::where('tenant_id', $tenantId)
                ->where('class_arm_id', $classArm->id)
                ->where('term_id', $term->id)
                ->with('student:id,tenant_id,admission_number,first_name,middle_name,last_name')
                ->orderByRaw('CASE WHEN position_in_class IS NULL THEN 1 ELSE 0 END')
                ->orderBy('position_in_class')
                ->orderBy('student_id')
                ->get();
            $publication = ReportCardPublication::where('tenant_id', $tenantId)
                ->where('class_arm_id', $classArm->id)
                ->where('term_id', $term->id)
                ->with('publishedBy:id,name')
                ->first();
            $activeStudents = Student::where('tenant_id', $tenantId)
                ->where('current_class_arm_id', $classArm->id)
                ->where('status', Student::STATUS_ACTIVE)
                ->count();
        }

        return response()->json([
            'contract_version' => 2,
            'module' => [
                'key' => 'reports',
                'title' => 'Report Cards',
            ],
            'capabilities' => [
                'view' => true,
                'compute' => $this->canPublish($user),
                'publish' => $this->canPublish($user),
                'unpublish' => $this->canPublish($user),
                'edit_remarks' => $user->canAccessModule('reports.remarks'),
            ],
            'options' => [
                'class_arms' => $classArms->map(fn (ClassArm $arm): array => [
                    'id' => $arm->id,
                    'name' => trim(($arm->classLevel?->name ?? '').' '.$arm->name),
                    'class_level_id' => $arm->class_level_id,
                ])->values(),
                'terms' => $terms->map(fn (Term $option): array => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'session' => $option->session?->name,
                    'is_current' => (bool) $option->is_current,
                ])->values(),
            ],
            'selected' => [
                'class_arm_id' => $classArm?->id,
                'term_id' => $term?->id,
            ],
            'class' => $classArm ? [
                'id' => $classArm->id,
                'name' => trim(($classArm->classLevel?->name ?? '').' '.$classArm->name),
            ] : null,
            'term' => $term ? [
                'id' => $term->id,
                'name' => $term->name,
                'session' => $term->session?->name,
            ] : null,
            'publication' => $this->publicationPayload($publication),
            'summary' => [
                'computed' => $summaries->count(),
                'active_students' => $activeStudents,
                'missing' => $activeStudents === null ? null : max(0, $activeStudents - $summaries->count()),
                'published' => $publication?->isPublished() ?? false,
            ],
            'students' => $summaries->map(fn (TermlySummary $summary): array => $this->summaryPayload($summary))->values(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function compute(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        abort_unless($this->canPublish($user), 403, 'Only academic administrators can compute report cards.');
        $tenantId = (int) $user->tenant_id;
        $data = $this->validateSelection($request, $tenantId);

        $published = ReportCardPublication::where('tenant_id', $tenantId)
            ->where('class_arm_id', (int) $data['class_arm_id'])
            ->where('term_id', (int) $data['term_id'])
            ->where('status', 'published')
            ->exists();
        abort_if($published, 423, 'These report cards are published. Return them to draft before recomputing.');

        $computed = $this->computation->compute(
            $tenantId,
            (int) $data['class_arm_id'],
            (int) $data['term_id'],
        );

        return response()->json([
            'message' => "{$computed} report card(s) computed.",
            'computed' => $computed,
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        abort_unless($this->canPublish($user), 403, 'Only academic administrators can publish report cards.');
        $tenantId = (int) $user->tenant_id;
        $data = $this->validateSelection($request, $tenantId, includeNote: true);

        $result = $this->publication->publish(
            $tenantId,
            (int) $data['class_arm_id'],
            (int) $data['term_id'],
            $user,
            isset($data['note']) ? (string) $data['note'] : null,
            $request,
        );

        return response()->json([
            'message' => 'Report cards published. Parent and student result access is now unlocked.',
            'publication' => $this->publicationPayload($result['publication']),
            'guardians_notified' => $result['guardians_notified'],
        ]);
    }

    public function unpublish(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        abort_unless($this->canPublish($user), 403, 'Only academic administrators can unpublish report cards.');
        $tenantId = (int) $user->tenant_id;
        $data = $this->validateSelection($request, $tenantId);

        $publication = $this->publication->unpublish(
            $tenantId,
            (int) $data['class_arm_id'],
            (int) $data['term_id'],
            $user,
            $request,
        );

        return response()->json([
            'message' => 'Report cards returned to draft. Score entry is unlocked again.',
            'publication' => $this->publicationPayload($publication),
        ]);
    }

    private function validateSelection(Request $request, int $tenantId, bool $includeNote = false): array
    {
        $rules = [
            'class_arm_id' => [
                'required', 'integer',
                Rule::exists('class_arms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'term_id' => [
                'required', 'integer',
                Rule::exists('terms', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ];
        if ($includeNote) {
            $rules['note'] = ['nullable', 'string', 'max:2000'];
        }

        return $request->validate($rules);
    }

    private function summaryPayload(TermlySummary $summary): array
    {
        $student = $summary->student;

        return [
            'summary_id' => $summary->id,
            'student' => [
                'id' => $summary->student_id,
                'name' => $student
                    ? trim(implode(' ', array_filter([$student->first_name, $student->middle_name, $student->last_name])))
                    : 'Student #'.$summary->student_id,
                'admission_number' => $student?->admission_number,
            ],
            'average' => (float) $summary->final_average,
            'position' => $summary->position_in_class,
            'class_size' => $summary->total_students_in_class,
            'subjects_offered' => (int) $summary->subjects_offered,
            'subjects_failed' => (int) $summary->subjects_failed,
            'promotion_status' => $summary->promotion_status,
            'form_tutor_remark' => $summary->form_tutor_remark,
            'principal_remark' => $summary->principal_remark,
            'subjects' => $summary->subject_breakdown ?: [],
            'computed_at' => $summary->computed_at?->toIso8601String(),
        ];
    }

    private function publicationPayload(?ReportCardPublication $publication): ?array
    {
        if (!$publication) {
            return null;
        }

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

    private function guard(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403);
        abort_unless($user->tenant_id, 403);
        abort_unless(
            $user->canAccessExactModule('reports'),
            403,
            'Full report-card access is required for this workspace.'
        );

        return $user;
    }

    private function canPublish(User $user): bool
    {
        return $user->canAccessExactModule('students');
    }
}
