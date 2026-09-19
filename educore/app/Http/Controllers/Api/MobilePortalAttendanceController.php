<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Term;
use App\Services\ParallelCurriculumPortalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobilePortalAttendanceController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumPortalService $parallelPortal,
    ) {}

    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user && ($user->isStudent() || $user->isParent()), 403);

        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'child_id' => ['nullable', 'integer'],
            'term_id' => [
                'nullable',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        [$student, $children] = $this->studentContext(
            $request,
            isset($data['child_id']) ? (int) $data['child_id'] : null,
        );

        $terms = Term::query()
            ->with('session')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        $requestedTermId = isset($data['term_id']) ? (int) $data['term_id'] : null;
        $termId = $requestedTermId && $terms->contains('id', $requestedTermId)
            ? $requestedTermId
            : (int) optional($terms->firstWhere('is_current', true) ?? $terms->first())->id;

        $records = AttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->when($termId, fn ($query) => $query->where('term_id', $termId))
            ->orderByDesc('attendance_date')
            ->get();

        $stats = [
            'total' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'excused' => $records->where('status', 'excused')->count(),
        ];
        $stats['rate'] = $stats['total'] > 0
            ? round(($stats['present'] / $stats['total']) * 100, 1)
            : 0.0;

        $parallel = $this->parallelPortal
            ->attendanceForStudent($student, $termId ?: null)
            ->map(fn (array $programme): array => [
                'curriculum_id' => $programme['curriculum_id'],
                'curriculum_name' => $programme['curriculum_name'],
                'class_name' => $programme['class_name'],
                'arm_name' => $programme['arm_name'],
                'stats' => $programme['stats'],
                'records' => $programme['records']->map(fn ($record) => [
                    'date' => $record->attendance_date?->toDateString(),
                    'status' => $record->status,
                    'remark' => $record->remark,
                ])->values(),
            ])
            ->values();

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'portal' => $user->isParent() ? 'parent' : 'student',
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'class' => $student->currentClassArm ? [
                    'id' => $student->currentClassArm->id,
                    'name' => $student->currentClassArm->full_name,
                ] : null,
            ],
            'children' => $children->map(fn (Student $child) => [
                'id' => $child->id,
                'name' => $child->full_name,
            ])->values(),
            'terms' => $terms->map(fn (Term $term) => [
                'id' => $term->id,
                'name' => $term->name,
                'session_name' => $term->session?->name,
                'is_current' => (bool) $term->is_current,
            ])->values(),
            'selected_term_id' => $termId ?: null,
            'conventional' => [
                'stats' => $stats,
                'records' => $records->map(fn (AttendanceRecord $record) => [
                    'date' => $record->attendance_date?->toDateString(),
                    'status' => $record->status,
                    'remark' => $record->remark,
                ])->values(),
            ],
            'parallel_programmes' => $parallel,
        ]);
    }

    private function studentContext(Request $request, ?int $childId): array
    {
        $user = $request->user();

        if ($user->isStudent()) {
            $student = Student::query()
                ->with('currentClassArm.classLevel')
                ->where('tenant_id', $user->tenant_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            return [$student, collect()];
        }

        $guardian = Guardian::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $children = $guardian->students()
            ->with('currentClassArm.classLevel')
            ->where('students.status', Student::STATUS_ACTIVE)
            ->orderBy('students.first_name')
            ->orderBy('students.last_name')
            ->get();

        abort_if($children->isEmpty(), 404, 'No active learner is linked to this parent account.');

        $student = $childId
            ? $children->firstWhere('id', $childId)
            : $children->first();

        abort_unless($student, 403, 'This learner is not linked to your parent account.');

        return [$student, $children];
    }
}
