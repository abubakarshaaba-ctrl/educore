<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\Student;
use App\Models\Term;
use App\Services\Mobile\MobileClassAccessService;
use App\Services\MobileReportCardService;
use Illuminate\Http\Request;

class MobileClassController extends Controller
{
    public function __construct(private readonly MobileClassAccessService $access) {}

    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = $this->access->accessibleClasses($request->user())
            ->withCount(['students as active_students_count' => fn ($builder) => $builder->active()])
            ->when($data['search'] ?? null, function ($builder, string $search): void {
                $builder->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhereHas('classLevel', fn ($level) => $level->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('class_level_id')
            ->orderBy('name');

        $paginator = $query->paginate($data['per_page'] ?? 30);
        $classes = collect($paginator->items())
            ->map(fn (ClassArm $classArm) => $this->access->classPayload($request->user(), $classArm));

        return response()->json([
            'contract_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'classes' => $classes,
            'meta' => $this->pagination($paginator),
        ]);
    }

    public function show(Request $request, int $classArmId)
    {
        $classArm = ClassArm::with(['classLevel', 'academicTrack', 'formTutor:id,name'])->findOrFail($classArmId);
        abort_unless($this->access->canViewClass($request->user(), $classArm), 403, 'You are not assigned to this class.');

        return response()->json([
            'contract_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'class' => $this->access->classPayload($request->user(), $classArm),
        ]);
    }

    public function students(Request $request, int $classArmId)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $classArm = ClassArm::with('classLevel')->findOrFail($classArmId);
        abort_unless($this->access->canViewClass($request->user(), $classArm), 403, 'You are not assigned to this class.');

        $paginator = $classArm->students()
            ->active()
            ->when($data['search'] ?? null, function ($builder, string $search): void {
                $builder->where(function ($nested) use ($search): void {
                    $nested->where('admission_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($data['per_page'] ?? 50);

        return response()->json([
            'contract_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'class' => $this->access->classPayload($request->user(), $classArm),
            'students' => collect($paginator->items())->map(fn (Student $student) => $this->studentSummary($student)),
            'meta' => $this->pagination($paginator),
        ]);
    }

    public function student(Request $request, int $classArmId, int $studentId)
    {
        $classArm = ClassArm::with('classLevel')->findOrFail($classArmId);
        abort_unless($this->access->canViewClass($request->user(), $classArm), 403, 'You are not assigned to this class.');
        $student = $classArm->students()->active()->findOrFail($studentId);
        $termId = Term::current()->value('id');
        $records = AttendanceRecord::where('student_id', $student->id)
            ->when($termId, fn ($builder, int $id) => $builder->where('term_id', $id))
            ->get();
        $present = $records->whereIn('status', ['present', 'late'])->count();

        return response()->json([
            'contract_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'class' => [
                'id' => $classArm->id,
                'name' => trim(($classArm->classLevel?->name ?? '').' '.$classArm->name),
            ],
            'student' => array_merge($this->studentSummary($student), [
                'date_of_birth' => $student->date_of_birth?->toDateString(),
                'status' => $student->status,
                'admission_date' => $student->admission_date?->toDateString(),
                'attendance' => [
                    'present' => $present,
                    'absent' => $records->where('status', 'absent')->count(),
                    'late' => $records->where('status', 'late')->count(),
                    'excused' => $records->where('status', 'excused')->count(),
                    'total' => $records->count(),
                    'rate' => $records->isNotEmpty() ? round(($present / $records->count()) * 100, 1) : 0,
                ],
            ]),
        ]);
    }

    /**
     * Published report cards for a student inside a class the authenticated
     * staff member is allowed to view. This deliberately reuses the same
     * MobileReportCardService contract consumed by student and parent apps.
     */
    public function results(
        Request $request,
        int $classArmId,
        int $studentId,
        MobileReportCardService $reports,
    ) {
        $classArm = ClassArm::with('classLevel')->findOrFail($classArmId);
        abort_unless(
            $this->access->canViewClass($request->user(), $classArm),
            403,
            'You are not assigned to this class.'
        );

        $student = $classArm->students()->active()->findOrFail($studentId);

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'class' => [
                    'id' => $classArm->id,
                    'name' => trim(($classArm->classLevel?->name ?? '').' '.$classArm->name),
                ],
            ],
            'results' => $reports->forStudent($student),
        ]);
    }

    private function studentSummary(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->full_name,
            'admission_number' => $student->admission_number,
            'gender' => $student->gender,
            'initials' => collect([$student->first_name, $student->last_name])
                ->filter()
                ->map(fn (string $name) => mb_strtoupper(mb_substr($name, 0, 1)))
                ->join(''),
        ];
    }

    private function pagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
