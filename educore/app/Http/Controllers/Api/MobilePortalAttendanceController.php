<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MobilePortalAttendanceController extends Controller
{
    public function student(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user?->isStudent(), 403, 'Student portal access only.');
        abort_unless($user->tenant_id, 403);

        $tenantId = (int) $user->tenant_id;
        $student = Student::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->with('currentClassArm.classLevel')
            ->first();
        abort_unless($student, 403, 'No student profile is linked to this account.');

        return response()->json($this->payload(
            tenantId: $tenantId,
            student: $student,
            requestedTermId: $request->filled('term_id') ? $request->integer('term_id') : null,
            children: collect(),
            portal: 'student',
        ));
    }

    public function parent(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user?->isParent(), 403, 'Parent portal access only.');
        abort_unless($user->tenant_id, 403);

        $tenantId = (int) $user->tenant_id;
        $guardian = Guardian::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->first();
        abort_unless($guardian, 403, 'No guardian profile is linked to this account.');

        $children = $guardian->students()
            ->where('students.tenant_id', $tenantId)
            ->with('currentClassArm.classLevel')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $student = $children->first();
        if ($request->filled('child_id')) {
            $student = $children->firstWhere('id', $request->integer('child_id'));
            abort_unless($student, 403, 'This child is not linked to your parent account.');
        }

        return response()->json($this->payload(
            tenantId: $tenantId,
            student: $student,
            requestedTermId: $request->filled('term_id') ? $request->integer('term_id') : null,
            children: $children,
            portal: 'parent',
        ));
    }

    private function payload(
        int $tenantId,
        ?Student $student,
        ?int $requestedTermId,
        Collection $children,
        string $portal,
    ): array {
        $terms = Term::where('tenant_id', $tenantId)
            ->with('session:id,name')
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $term = $requestedTermId !== null
            ? $terms->firstWhere('id', $requestedTermId)
            : ($terms->firstWhere('is_current', true) ?? $terms->first());
        if ($requestedTermId !== null) {
            abort_unless($term, 422, 'The selected term is not available for this school.');
        }

        $records = ($student && $term)
            ? AttendanceRecord::where('tenant_id', $tenantId)
                ->where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->orderByDesc('attendance_date')
                ->limit(180)
                ->get(['attendance_date', 'status', 'remark'])
            : collect();

        $present = $records->where('status', 'present')->count();
        $late = $records->where('status', 'late')->count();
        $absent = $records->where('status', 'absent')->count();
        $rate = $records->isNotEmpty()
            ? round((($present + $late) / $records->count()) * 100, 1)
            : 0;

        return [
            'contract_version' => 1,
            'portal' => $portal,
            'student' => $student ? $this->studentPayload($student) : null,
            'children' => $children->map(fn (Student $child): array => $this->studentPayload($child))->values(),
            'terms' => $terms->map(fn (Term $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'session' => $item->session?->name,
                'is_current' => (bool) $item->is_current,
            ])->values(),
            'selected_term_id' => $term?->id,
            'records' => $records->map(fn (AttendanceRecord $record): array => [
                'date' => $record->attendance_date?->toDateString() ?? (string) $record->attendance_date,
                'status' => $record->status,
                'remark' => $record->remark,
            ])->values(),
            'stats' => [
                'total' => $records->count(),
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'rate' => $rate,
            ],
            'generated_at' => now()->toIso8601String(),
        ];
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
                'name' => trim(($student->currentClassArm->classLevel?->name ?? '').' '.$student->currentClassArm->name),
            ] : null,
        ];
    }
}
